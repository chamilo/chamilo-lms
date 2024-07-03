<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes previous tasks from disk to clear space.
 * Configure the date on line 15 to change the date before which to delete,
 * then delete the 'exit;' statement at line 13.
 * This works based on dates works were uploaded in Chamilo.
 * This script should be located inside the tests/scripts/ folder to work
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
exit;
require __DIR__.'/../../main/inc/global.inc.php';

$beforeDate = '2020-08-31'; //session start date must be > to be considered
$simulate = false;
$taskNameFilter = ''; // fill with any value to only delete tasks that contain this text
if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}
echo PHP_EOL."Usage: php ".basename(__FILE__)." [options]".PHP_EOL;
echo "Where [options] can be ".PHP_EOL;
echo "  -s         Simulate execution - Do not delete anything, just show numbers".PHP_EOL.PHP_EOL;
echo "Processing...".PHP_EOL;

if (!empty($argv[1]) && $argv[1] == '-s') {
    $simulate = true;
    echo "Simulation mode is enabled.".PHP_EOL;
}

$coursesCodes = array();
$coursesDirs = array();

$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$sql = "SELECT w.iid, w.c_id, w.url, w.sent_date, w.session_id, c.directory
    FROM $table w, $tableCourse c
    WHERE  w.c_id = c.id
      AND w.sent_date < '$beforeDate'
      AND w.filetype = 'file'
      AND w.contains_file = 1
      AND w.url != ''";
// url empty probably means we already deleted the file
$worksResult = Database::query($sql);
$countWorks = Database::num_rows($worksResult);

$pathToReplace = api_get_path(SYS_COURSE_PATH).'%s/%s';

$worksResultGeneral = Database::query(
    "SELECT count(iid) as nbr
    FROM $table
    WHERE filetype = 'file'
    AND contains_file = 1
    AND url != ''"
);
if ($worksResultGeneral === false) {
    die("Error querying total works\n");
}
$countAllWorks = Database::result($worksResultGeneral, 0, 'nbr');
echo "[".time()."]"
    ." Found $countWorks works before $beforeDate on a total of $countAllWorks works."."\n";

$totalSize = 0;
$countDeleted = 0;
while ($work = Database::fetch_assoc($worksResult)) {
    $path = sprintf($pathToReplace, $work['directory'], $work['url']);
    if (file_exists($path)) {
        //echo $path.' found'.PHP_EOL;
        $sqlUpdate = "UPDATE $table
            SET url = '',
            contains_file = 0
            WHERE iid = ".$work['iid'];
        //echo $sqlUpdate.PHP_EOL;
        $totalSize += filesize($path);
        $countDeleted++;
        if ($simulate == false) {
            exec('rm -f '.$path);
            Database::query($sqlUpdate);
        }
        // We don't really need to delete the work itself, but if we wanted
        // that, the following code would do it
        /*
        api_item_property_delete(
            api_get_course_info_by_id($work['c_id']),
            TOOL_STUDENTPUBLICATION,
            $work['iid'],
            null,
            null,
            $work['session_id']
        );
        $sqlDelete = "DELETE FROM $table WHERE iid = ".$work['iid'];
        Database::query($sqlUpdate);
        */
    }
}

echo "[".time()."] Deleted $countDeleted works, for a total of ".round($totalSize / (1024 * 1024))." MB....".PHP_EOL;
exit;
