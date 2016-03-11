<?php
require_once dirname(__FILE__) . "/../addons/everypay/controllers/common/lib.php";

if(isset($_POST['everypayToken'])){
    fn_everypay_send_payment();
} else {
    fn_everypay_button();
}
exit;
?>