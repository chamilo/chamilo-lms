<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes old tracking records from the database.
 * It uses parameters in order (all mandatory but the last one).
 * Delete the exit; statement at line 13.
 * This script should be located inside the tests/scripts/ folder to work
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
exit;
require __DIR__.'/../../main/inc/global.inc.php';
$simulate = false;

// Process script parameters
if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

if (!empty($argv[1]) && $argv[1] == '--from') {
    $from = $argv[2];
}
if (!empty($argv[3]) && $argv[3] == '--until') {
    $until = $argv[4];
}
if (!empty($argv[5]) && $argv[5] == '-s') {
    $simulate = true;
    echo "Simulation mode is enabled".PHP_EOL;
}
if (empty($from) or empty($until)) {
    echo PHP_EOL."Usage: sudo php ".basename(__FILE__)." [options]".PHP_EOL;
    echo "Where [options] can be ".PHP_EOL;
    echo "  --from yyyy-mm-dd    Date from which the content should be removed (e.g. 2017-08-31)".PHP_EOL.PHP_EOL;
    echo "  --until yyyy-mm-dd   Date up to which the content should be removed (e.g. 2020-08-31)".PHP_EOL.PHP_EOL;
    echo "  -s                   (optional) Simulate execution - Do not delete anything, just show numbers".PHP_EOL.PHP_EOL;
    die('Please make sure --from and --until are defined.');
}

echo "About to delete tracking records from $from to $until".PHP_EOL;

// list of tables with their date field
$fields = [
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS) => 'access_date',
    //Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT) => 'tms',
    //track_e_attempt_coeff ?
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING) => 'insert_date',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS) => 'login_course_date',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT) => 'default_date',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS) => 'down_date',
    //Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES) => 'exe_date',
    //Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES) => 'exe_date',
    //Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT) => '#linked to exe_id#',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY) => 'lastedit_date',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS) => 'access_date',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_LINKS) => 'links_date',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN) => 'login_date',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE) => 'login_date',
    //Database::get_main_table(TABLE_STATISTIC_TRACK_E_OPEN) => 'open_date',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_UPLOADS) => 'upload_date',
    //track_stored_values
    //track_stored_values_stack
];

echo deleteTracking($from, $until, $fields, $simulate);

/**
 * Delete all data from the tracking tables between the given dates and return a log string.
 * @param string $from  'yyyy-mm-dd' format date from which to start deleting
 * @param string $until 'yyyy-mm-dd' format date until which to delete
 * @param array $fields An array with the list of tables and their date field
 * @param bool   $simulate True if we only want to simulate the deletion and collect data
 * @return string
 */
function deleteTracking(string $from, string $until, array $fields, bool $simulate): string
{
    $log = '';
    $size = 0;
    if ($simulate) {
        $log .= 'Simulation mode ON'.PHP_EOL;
    }
    foreach ($fields as $table => $field) {
        $sql = "SELECT count(*) as countrows FROM $table WHERE $field > '$from 00:00:00' AND $field < '$until 23:59:59'";
        $query = Database::query($sql);
        $count = Database::result($query, 0, 'countrows');
        $size += $count;
        $log .= 'About to delete '.$count.' records from '.$table.'.'.PHP_EOL;
        $sql = "DELETE FROM $table WHERE $field > '$from 00:00:00' AND $field < '$until 23:59:59'";
        $log .= $sql.PHP_EOL;
        if (!$simulate) {
            Database::query($sql);
        }
    }
    $log .= 'Found '.$size.' matching records in total.'.PHP_EOL;

    return $log;
}
