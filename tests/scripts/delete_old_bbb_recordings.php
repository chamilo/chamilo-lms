<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes old BigBlueButton recordings from the database and disk.
 * It uses parameters in order (all mandatory but the last one).
 * Delete the exit; statement at line 13.
 * This script should be located inside the tests/scripts/ folder to work.
 * The script also generates output (which you should pipe to a file) with
 * the list of internal-meeting-id's of the deleted recordings, so you can
 * delete them from the BBB server.
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
if ($argc < 2 or empty($from) or empty($until)) {
    echo PHP_EOL."Usage: sudo php ".basename(__FILE__)." [options]".PHP_EOL;
    echo "Where [options] can be ".PHP_EOL;
    echo "  --from yyyy-mm-dd    Date from which the content should be removed (e.g. 2017-08-31)".PHP_EOL.PHP_EOL;
    echo "  --until yyyy-mm-dd   Date up to which the content should be removed (e.g. 2020-08-31)".PHP_EOL.PHP_EOL;
    echo "  -s                   (optional) Simulate execution - Do not delete anything, just show numbers".PHP_EOL.PHP_EOL;
    die('Please make sure --from and --until are defined.');
}

echo "About to delete BigBlueButton recordings from $from to $until".PHP_EOL;

$settingsTable = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
$sql = "SELECT selected_value from $settingsTable WHERE variable = 'bbb_tool_enable'";
$res = Database::query($sql);
$row = Database::fetch_assoc($res);
if ($row['selected_value'] != 'true') {
    die('The BigBlueButton plugin must be enabled to execute this script.'.PHP_EOL);
}

$return = deleteBBBRecordings($from, $until, $simulate);
$commands = '';
foreach ($return as $line) {
    if (preg_match('/^bbb-record/', $line)) {
        $commands .= $line;
    } else {
        echo $line;
    }
}
echo '----------------'.PHP_EOL;
echo $commands.PHP_EOL;

/**
 * Delete all data from the BBB plugin table between the given dates and return a log string.
 * @param string $from  'yyyy-mm-dd' format date from which to start deleting
 * @param string $until 'yyyy-mm-dd' format date until which to delete
 * @param bool   $simulate True if we only want to simulate the deletion and collect data
 * @return array
 */
function deleteBBBRecordings(string $from, string $until, bool $simulate): array
{
    $log = [];
    $size = 0;
    if ($simulate) {
        $log[] = 'Simulation mode ON'.PHP_EOL;
    }
    $table = 'plugin_bbb_meeting';
    $tableFormat = 'plugin_bbb_meeting_format';
    $tableRoom = 'plugin_bbb_room';
    // Get the list of sessions where access_end_date is within the given range
    $sessions = Database::select(
        ['id', 'internal_meeting_id'],
        $table,
        [
            'where' => ['closed_at > ? AND closed_at < ? AND record = 1 AND internal_meeting_id is not null' => [$from.' 00:00:00', $until.' 23:59:00']],
        ]
    );
    $log[] = 'Found '.count($sessions).' recordings to delete'.PHP_EOL;
    foreach ($sessions as $session) {
        //$log[] = 'Deleting recording '.$session['id'].PHP_EOL;
        if (!$simulate) {
            $sqlDelete = 'DELETE FROM '.$tableRoom.' WHERE meeting_id = '.$session['id'];
            $resDelete = Database::query($sqlDelete);
            $sqlDelete = 'DELETE FROM '.$tableFormat.' WHERE meeting_id = '.$session['id'];
            $resDelete = Database::query($sqlDelete);
            $sqlDelete = 'DELETE FROM '.$table.' WHERE id = '.$session['id'];
            $resDelete = Database::query($sqlDelete);
        }
        $log[] = 'bbb-record --delete '.$session['internal_meeting_id'].PHP_EOL;
        $size++;
    }
    $log[] = 'Deleted '.$size.' recordings (and their content) in total.'.PHP_EOL;

    return $log;
}
