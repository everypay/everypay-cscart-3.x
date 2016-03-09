<?php
require_once dirname(__FILE__) . "/controllers/common/lib.php";


function fn_everypay_find_payment_processor(){
    $processor_data = db_get_row("SELECT * FROM ?:payment_processors WHERE addon = ?s", 'everypay');
    
    if (empty($processor_data)) {
        return false;
    }
    return $processor_data;
}

