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
$num_trans = 500; //how many transactions to process during each execution
// Check for pidfile. Normally, no concurrent write is possible as transactions
//  should be started at about 120 seconds intervals.
$pidfile = __DIR__.'/chamilo.transaction.pid';
$pid = getmypid();
if (is_file($pidfile)) {
  $pid = file_get_contents($pidfile);
  error_log("Transaction run frozen: PID file already exists with PID $pid in $pidfile");
  die('PID exists - Transaction run frozen');
} else {
  $res = file_put_contents($pidfile,$pid);
  if ($res === false) {
    error_log('Failed writing PID file - Transaction run frozen');
    die('Failed writing PID file - Transaction run frozen');
  }
  error_log('Written PID file with PID '.$pid.'. Now starting transaction run');
}

$cron_start = time();
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
    //die();
} else {
    $mode = $argv[1];
}
$branch_id = 0;
// We need $branch_id defined before calling db_matches.php
// The only thing we need from db_matches is the definition of the web service
require_once 'db_matches.php';
/**
 * Process
 */   
$migration = new Migration();    
$migration->set_web_service_connection_info($matches);    
require $migration->web_service_connection_info['filename'];
$mig = new $migration->web_service_connection_info['class'];
error_log('Building in-memory data_list for speed-up '.time());
$data_list = array('boost_users'=>true, 'boost_courses'=>true, 'boost_sessions'=>true);
/**
 * Build an array of in-memory database data to reduce time spent querying
 */
if (count($data_list['users'])<1) {
    MigrationCustom::fill_data_list($data_list);
}
error_log('Built in-memory data_list for speed-up '.time());

// Counter for transactions found and dealt with
$count_transactions = 0;
/**
 * Check each branch for transactions to execute (and execute them)
 */
$branches = $migration->get_branches();
foreach ($branches as $id => $branch) {
    $response = '';
    $branch_id = $branch['branch_id'];
    if ($mode == 'process') {
        //Load transactions saved before
	$params = array('branch_id' => $branch_id, 'number_of_transactions' => $num_trans);
        $migration->get_transactions_from_webservice($params);
        $count_transactions += $migration->execute_transactions($params);

    } else {
        //if mode==fix
        error_log('Fixing transactions');
        $params = array('branch_id' => $branch_id, 'number_of_transactions' => $num_trans);
        $migration->execute_transactions($params);
    }
}
/**
 * Free the PID file used as semaphore
 */
if (is_file($pidfile)) {
  $opid = trim(file_get_contents($pidfile));
  if (intval($opid) == intval($pid)) {
    $res = @exec('rm '.$pidfile);
    if ($res === false) {
      error_log('Could not delete PID file');
      die('Could not delete PID file');
    }
    error_log('PID file deleted for PID '.$pid);
    error_log(str_repeat('=',40));
  } else {
    error_log('PID file is not of current process. Not deleting.');
    die('PID file is not of current process. Not deleting.'."\n");
  }
}
$cron_total = time() - $cron_start;
error_log('Total time taken for transaction run: '.$cron_total.'s for '.$count_transactions.' transactions');
