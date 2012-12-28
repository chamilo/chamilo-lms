<?php
/**
 * This script should be called by cron.d to update items based on stored
 * transactions
 * @package chamilo.migration
 */
/**
 * Init
 */
if (PHP_SAPI != 'cli') {
    exit;
}
require_once dirname(__FILE__).'/../../main/inc/global.inc.php';
require_once 'config.php';
require_once 'migration.class.php'; 
// If the script if called with a 'fix' argument, then deal with it differently
// (do not call the webservice, but instead try to re-execute previously
// failed transactions). The default mode is "process".
$modes = array('fix','process');
$mode = 'process';
if (($argc < 2) or empty($argv[1])) {
    error_log('No mode provided for transaction cron.d process in '.__FILE__.', assuming "process"');
} elseif (!in_array($argv[1],$modes)) {
    error_log('Mode '.$argv[1].' not recognized in '.__FILE__);
    die();
} else {
    $mode = $argv[1];
}
$branch_id = 0;
// We need $branch_id defined before calling db_matches.php
// The only thing we need from db_matches is the definition of the web service
require_once 'db_matches.php';
    
$migration = new Migration();    
$migration->set_web_service_connection_info($matches);    
require $migration->web_service_connection_info['filename'];
$mig = new $migration->web_service_connection_info['class'];

// Check all branches one by one
$branches = $migration->get_branches();
foreach ($branches as $id => $branch) { 
    $response = '';
    $branch_id = $branch['branch_id'];
    if ($mode == 'process') {
        //Load transactions saved before
        $params = array('branch_id' => $branch_id, 'number_of_transactions' => '100');
        $migration->get_transactions_from_webservice($params);
        $migration->execute_transactions($params);

//        $trans_id = $migration->get_latest_transaction_id_by_branch($branch_id);
//        error_log("Last transaction was $trans_id for branch $branch_id");
//        $params = array(
//            'ultimo' => $trans_id,
//            'cantidad' => 100,
//            'intIdSede' => $branch_id,
//        );
//        $result = $mig->process_transactions($params,$migration->web_service_connection_info);
    } else {
        //if mode==fix
        error_log('Fixing transactions');
        $params = array('branch_id' => $branch_id, 'number_of_transactions' => '100');
        $migration->execute_transactions($params);
    }
    //$result = $migration->load_transaction_by_third_party_id($trans_id, $branch_id);
    //$response .= $result['message'];
    //if (isset($result['raw_reponse'])) {
    //    $response .= $result['raw_reponse'];
    //}
    //if (!empty($response)) {
    //    error_log($response);
    //}
}
