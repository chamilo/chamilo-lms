<?php

require_once dirname(__FILE__).'/../../main/inc/global.inc.php';
require_once 'config.php';

Display::display_header();

$form = new FormValidator('transaction_tester');
$form->addElement('header', 'Transaction tester');
$form->addElement('text', 'transaction_id', get_lang('TransactionId'));
$form->addRule('transaction_id',get_lang('ThisFieldShouldBeNumeric'),'numeric');
$form->addElement('button', 'submit', get_lang('Send'));

$response = null;

if ($form->validate()) {
    $values = $form->getSubmitValues();    
    $transaction_id = $values['transaction_id'];
    $response = Display::page_subheader2("Executing transaction #$transaction_id");    
    
    require_once 'migration.class.php'; 
    require_once 'migration.custom.class.php';
    //harcoded db_matches
    require_once 'db_matches.php';
    
    $migration = new Migration();    
    $migration->set_web_service_connection_info($matches);
    
    
    //This is the fault of the webservice
    $transaction_id--;    
    $result = $migration->load_transaction_by_third_party_id($transaction_id);    
    $response .= $result['message'];
    if (isset($result['raw_reponse'])) {
        $response .= $result['raw_reponse'];
    }    
}

$form->setDefaults(array('transaction_id' => '376012'));
$form->display();

if (!empty($response)) {
    echo $response;
}

