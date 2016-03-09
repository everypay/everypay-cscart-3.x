var EPBUTTON, $EVERYPAYFORM, $EVERYPAYIFRAME;
$( document ).ready(function() {

    function add_everypay_css() {
        if ($('#everypay-css').length) {
            return;
        }
        var my_css = '.button-holder {display:none !important}';
        var head = document.head || document.getElementsByTagName('head')[0];
        var my_style = document.createElement('style');
        my_style.id = 'everypay-css';
        my_style.type = 'text/css';
        if (my_style.styleSheet) {
            my_style.styleSheet.cssText = my_css;
        } else {
            my_style.appendChild(document.createTextNode(my_css));
        }

        head.appendChild(my_style);
    }

    function init_button(INIT_DATA) {
        add_everypay_scripts();
        add_everypay_css();

        $EVERYPAYFORM = jQuery('#step_four_body form:visible');
        
        console.log($EVERYPAYFORM);
        
        $EVERYPAYIFRAME = $EVERYPAYFORM.find(".payment-method-iframe-box iframe");        
        $EVERYPAYFORM.append('<input type="hidden" value="1" name="dispatch[checkout.place_order]">');

        var loadButton = setInterval(function () {
            try {
                $EVERYPAYIFRAME.css('height', '130px')
                .parent().css('height', '130px')
                .find('.ty-payment-method-iframe__label').css('top', '30px');
                EPBUTTON = EverypayButton.jsonInit(INIT_DATA, $EVERYPAYFORM);
                clearInterval(loadButton);
            } catch (err) {
                //console.log(err);
            }
        }, 301);
    }

    function add_everypay_scripts() {
        if ($('#everypay-javascript').length) {
            return;
        }
        var head = document.head || document.getElementsByTagName('head')[0];
        var $btn_script = document.createElement('script');
        $btn_script.id = 'everypay-javascript';
        $btn_script.type = 'text/javascript';
        $btn_script.src = 'https://button.everypay.gr/js/button.js';
        head.appendChild($btn_script);
    }

    init_button(EVERYPAY_DATA);
});

function handleToken(message){
    var loadtext = 'Γίνεται αποστολή. Παρακαλούμε περιμένετε...';
    if (EVERYPAY_DATA.locale != 'el'){
        loadtext = 'Processing. Please wait...'
    }
    
    $EVERYPAYIFRAME.parent().hide()
    .before('<center style="clear:both;font-size:1.3em;padding:20px 0;">'+loadtext+'<br /><img src="data:image/gif;base64,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"></center>');
    //EverypayButton.appendTokenAndSubmit(message.token);
}

function trigger_outer_button() {
    EPBUTTON.click();
}