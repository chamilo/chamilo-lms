<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes old sessions from the database and disk.
 * It uses parameters in order (all mandatory but the last one).
 * Delete the exit; statement at line 13.
 * This script should be located inside the tests/scripts/ folder to work.
 * To really delete documents from disk, make sure the DB setting
 * 'permanently_remove_deleted_files' is set to 'true'.
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

echo "About to delete sessions from $from to $until".PHP_EOL;

echo deleteSessions($from, $until, $simulate);

/**
 * Delete all data from the tracking tables between the given dates and return a log string.
 * @param string $from  'yyyy-mm-dd' format date from which to start deleting
 * @param string $until 'yyyy-mm-dd' format date until which to delete
 * @param bool   $simulate True if we only want to simulate the deletion and collect data
 * @return string
 */
function deleteSessions(string $from, string $until, bool $simulate): string
{
    $log = '';
    $size = 0;
    if ($simulate) {
        $log .= 'Simulation mode ON'.PHP_EOL;
    }
    $table = Database::get_main_table(TABLE_MAIN_SESSION);
    // Get the list of sessions where access_end_date is within the given range
    $sessions = Database::select(
        'id',
        $table,
        [
            'where' => ['access_end_date > ? AND access_end_date < ?' => [$from.' 00:00:00', $until.' 23:59:00']],
        ]
    );
    $log .= 'Found '.count($sessions).' sessions to delete'.PHP_EOL;
    foreach ($sessions as $session) {
        $log .= 'Deleting session '.$session['id'].PHP_EOL;
        if (!$simulate) {
            SessionManager::delete($session['id'], true);
        }
        $size++;
    }
    $log .= 'Deleted '.$size.' sessions (and their content) in total.'.PHP_EOL;

    return $log;
}
