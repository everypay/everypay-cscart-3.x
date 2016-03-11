<?php

/**
 * Note: $to_currency will always be 'EUR'
 * 
 * @param float $price
 * @param string $from_currency
 * @param string $to_currency
 * @return array
 */
function fn_everypay_convert_amount($price, $from_currency, $to_currency)
{
    $currencies = Registry::get('currencies');
    //$symbol = $currencies[$to_currency]['symbol'];
    $symbol = '€';
    if ($to_currency == $from_currency) {
        return array('price' => $price, 'symbol' => $symbol);
    }
    if (array_key_exists($to_currency, $currencies)) {
        $price = fn_format_price($price / $currencies[$to_currency]['coefficient']);
        //$symbol = $currencies[$to_currency]['symbol'];
    } else {
        return 0;
    }
    $symbol = '€';
    return array('price' => $price, 'symbol' => $symbol);
}

function fn_everypay_place_order($order_id)
{
    db_query("REPLACE INTO ?:order_data (order_id, type, data) VALUES (?i, 'O', ?i)", $order_id, TIME);

    return $order_id;
}

function fn_everypay_button()
{
    $cart = & $_SESSION['cart'];
    $payment_id = $cart['payment_id'];
    
    if (!isset($_GET['dispatch']) || $_GET['dispatch'] != 'checkout.process_payment') {
        return;
    }

    $ev_id = 0;
    $processor_data = fn_get_payment_method_data($payment_id);
    if (empty($processor_data) || $processor_data['processor'] != 'Everypay') {
        die();
    }
    $processor_data = $processor_data['params'];    
    
    $amount = fn_everypay_convert_amount($cart['total'] + $cart['payment_surcharge'], CART_PRIMARY_CURRENCY, $processor_data['currency']);

    $lang = strtolower(CART_LANGUAGE);
    
    $jsonInit = array(
        'amount' => intval(strval($amount['price'] * 100)),
        'currency' => $processor_data['currency'],
        'key' => $processor_data['public_key'],
        'locale' => $lang == 'el' ? $lang : 'en',
        'sandbox' => $processor_data['test_mode'],
        'callback' => 'handleToken'
    );

    $max_installments = fn_everypay_get_installments($amount['price'], $cart['payment_method_data']['params']['everypay_installments']);

    $jsonInit['max_installments'] = $max_installments ? : 0;
    $time = time();

    $btn_text = 'Πληρωμή με κάρτα';
    if ($lang != 'el') {
        $btn_text = 'Pay with card';
    }
    //ouput
    ?>
    <html>
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
            <script type="text/javascript">
                var loadOutter = setInterval(function () {
                    try {
                        parent.init<?php echo $time ?> = function (data) {
                            var fileref = document.createElement("script")
                            fileref.setAttribute("type", "text/javascript")
                            fileref.setAttribute("id", "everypay_added_script_<?php echo $time ?>")
                            fileref.setAttribute("src", "/js/addons/everypay/everypay.js?ver=<?php echo $time ?>");
                            parent.document.getElementsByTagName("head")[0].appendChild(fileref)

                            parent.EVERYPAY_DATA = data;
                        };
                        parent.init<?php echo $time ?>(<?php echo json_encode($jsonInit) ?>);

                        clearInterval(loadOutter);
                    } catch (err) {
                        //console.log(err);
                    }
                }, 301);
                var trigger_button = function () {
                    parent.trigger_outer_button();
                }
            </script>
            <link type="text/css" rel="stylesheet" href="https://button.everypay.gr/css/button-external.css?version=1.76">
            <style type="text/css">
                .everypay-button {
                    font-family: Open Sans,sans-serif,Tahoma,Verdana;
                    font-size: 18px !important;
                    padding: 12px 20px !important;
                    text-decoration: none;
                }
            </style>
        </head>
        <body>
            <div style="text-align:center">
                <button onclick="trigger_button();" class="everypay-button"><?php echo $btn_text ?></button>
            </div>
        </body>
    </html>
    <?php
    die();
}

function fn_everypay_send_payment()
{
    $cart = & $_SESSION['cart'];
    $auth = & $_SESSION['auth'];

    $merchant_order_id = $cart['processed_order_id'][0];
    fn_everypay_place_order($merchant_order_id);

    if (!isset($_REQUEST['everypayToken']) || empty($_REQUEST['everypayToken'])) {
        fn_set_notification('E', fn_get_lang_var('error'), fn_get_lang_var('text_evp_failed_order'));
        fn_order_placement_routines('checkout_redirect');
    }

    $everypay_token = $_REQUEST['everypayToken'];

    if (!empty($merchant_order_id)) {
        if (fn_check_payment_script('everypay.php', $merchant_order_id, $processor_data)) {
            $secret_key = $processor_data['params']['secret_key'];
            $order_info = fn_get_order_info($merchant_order_id);

            $amount = fn_everypay_convert_amount($order_info['total'], CART_PRIMARY_CURRENCY, $processor_data['params']['currency']);

            $description = $_SERVER['SERVER_NAME']
                . ' - '
                . fn_get_lang_var('Order') . ' #' . $merchant_order_id
                . ' - '
                . $amount['price'] . ' ' . $amount['symbol'];

            $test_mode = $processor_data['params']['test_mode'];

            $theURL = "https://" . ($test_mode ? 'sandbox-' : '')
                . "api.everypay.gr/payments";

            $everypayParams = array(
                'token' => $everypay_token,
                'amount' => intval(strval($amount['price'] * 100)),
                'description' => $description,
                'payee_email' => $order_info['email'],
                'payee_phone' => $order_info['phone'],
            );

            if (false !== $max = fn_everypay_get_installments($order_info['total'], 
                $processor_data['params']['everypay_installments'])) {
                $everypayParams['max_installments'] = $max;
            }

            $response = array();
            $success = false;
            $error = "";

            try {
                $curl = curl_init();

                curl_setopt($curl, CURLOPT_TIMEOUT, 60);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($curl, CURLOPT_USERPWD, $secret_key . ':');
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($everypayParams, null, '&'));
                curl_setopt($curl, CURLOPT_URL, $theURL);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                $result = curl_exec($curl);
                $info = curl_getinfo($curl);
                
                if ($result === false) {
                    $success = false;
                    $error = 'Curl error: ' . curl_error($curl);
                } else {
                    $response_array = json_decode($result, true);
                    //Check success response
                    if (isset($response_array['error']) === false) {
                        $success = true;
                    } else {
                        $success = false;

                        if (!empty($response_array['error']['code'])) {
                            $error = $response_array['error']['code'] . ":" . $response_array['error']['message'];
                        } else {
                            $error = "ERROR:Invalid Response <br/>" . $result;
                        }
                    }
                }

                curl_close($curl);
            } catch (Exception $e) {
                $success = false;
                $error = "CSCART_ERROR:Request to Payment Gateway Failed";
            }
            
            if ($success === true) {
                $response['order_status'] = 'P';
                $response['reason_text'] = fn_get_lang_var('text_evp_success');
                $response['transaction_id'] = $merchant_order_id;
                $response['client_id'] = $everypay_token;
                fn_finish_payment($merchant_order_id, $response);
                fn_order_placement_routines($merchant_order_id);
            } else {
                $response['order_status'] = 'F'; //Failed
                if ($response_array['error']['code'] == 40004){
                    $response['order_status'] = 'D';  //Declined 
                }                
                $response['reason_text'] = '<strong>' . fn_get_lang_var('error') . '</strong> ' 
                    . fn_get_lang_var('text_evp_pending') . $everypay_token . ' (EveryPay: ' . $error . ')';
                $response['transaction_id'] = $merchant_order_id;
                $response['client_id'] = $everypay_token;
                fn_finish_payment($merchant_order_id, $response);
                //fn_set_notification('E', fn_get_lang_var('error'), fn_get_lang_var('text_evp_pending') . $everypay_token . ' (EveryPay: ' . $error . ')');
                fn_order_placement_routines($merchant_order_id);
            }
        }
    } else {
        fn_set_notification('E', fn_get_lang_var('error'), fn_get_lang_var('text_evp_failed_order'));
        fn_order_placement_routines('checkout_redirect');
    }

    exit;
}

function fn_everypay_find_payment_processor(){
    $processor_data = db_get_row("SELECT * FROM ?:payment_processors WHERE addon = ?s", 'everypay');
    
    if (empty($processor_data)) {
        return false;
    }
    return $processor_data;
}

function fn_everypay_get_installments($total, $ins)
{
    $inst = htmlspecialchars_decode($ins);
    if ($inst) {
        $installments = json_decode($inst, true);
        $counter = 1;
        $max = 0;
        $max_installments = 0;
        foreach ($installments as $i) {
            if ($i['to'] > $max) {
                $max = $i['to'];
                $max_installments = $i['max'];
            }

            if (($counter == (count($installments)) && $total >= $max)) {
                return $max_installments;
            }

            if ($total >= $i['from'] && $total <= $i['to']) {
                return $i['max'];
            }
            $counter++;
        }
    }
    return 0;
}
