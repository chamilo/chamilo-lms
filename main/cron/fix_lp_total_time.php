<?php
/* For licensing terms, see /license.txt */

/**
 * This script checks that the c_lp_item_view.total_time field
 * doesn't have big values. The scripts updates it or send a message to the admin (depending the settings).
 */
exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

opcache_reset();

$maxSeconds = 5 * 60 * 60; // Check records higher than 5 hours
$valueToUpdate = 1 * 60 * 60; // Update this abusive records with 1 hours
$limit = 10; // Only fix first 10
$sendMessage = true;
$userId = 1; // User id that will receive a report
$update = false;

$sql = "SELECT iid, total_time FROM c_lp_item_view 
        WHERE total_time > $maxSeconds 
        order by total_time desc 
        LIMIT $limit
";

$result = Database::query($sql);
$log = '';
while ($row = Database::fetch_array($result, 'ASSOC')) {
    $id = $row['iid'];
    $oldTotalTime = $row['total_time'];
    $sql = "UPDATE c_lp_item_view SET total_time = '$valueToUpdate' WHERE iid = $id;";
    // Uncomment to fix
    if ($update) {
        Database::query($sql);
    }

    $oldTotalTime = round($oldTotalTime / 3600, 2);
    $report = "Previous total_time : ".$oldTotalTime." hours";
    $report .= PHP_EOL;
    $report .= "New total_time: $valueToUpdate";
    $report .= PHP_EOL;
    $report .= $sql;
    $report .= PHP_EOL;
    $report .= PHP_EOL;
    $log .= $report;
    echo $report;
}

if ($sendMessage && !empty($log)) {
    $log = nl2br($log);
    MessageManager::send_message_simple(
        $userId,
        'LP time abusive fixes',
        $log,
        1
    );
}
