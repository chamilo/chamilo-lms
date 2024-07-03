<?php
/* For licensing terms, see /license.txt */
/**
 * This deletes courses that were created but no file was ever uploaded, and
 * that were created previous to a specific date and last used previous to
 * another specific date (see $creation and $access)
 * Use this script with caution, as it will completely remove any trace of the
 * deleted courses.
 * Please note that this is not written with the inclusion of the concept of
 * sessions. As such, it might delete courses but leave the course reference
 * in the session, which would cause issues.
 * Launch from the command line.
 * Usage: php delete_old_courses_even_not_empty.php
 */

exit;

require_once '../../main/inc/global.inc.php';

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}
echo PHP_EOL."Usage: php ".basename(__FILE__)." [options]".PHP_EOL;
echo "Where [options] can be ".PHP_EOL;
echo "  -s         Simulate execution - Do not delete anything, just show numbers".PHP_EOL.PHP_EOL;
echo "Processing...".PHP_EOL.PHP_EOL;

$simulate = false;
if (!empty($argv[1]) && $argv[1] == '-s') {
    $simulate = true;
    echo "Simulation mode is enabled".PHP_EOL;
}

// The date before which the course must have been created to be considered
$creation = '2022-08-31';
// The last date at which the course must have been accessed to be considered.
// If it was accessed *after* that date, it will NOT be considered for deletion.
$access = '2022-08-31';

$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tableAccess = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
$tableUser = Database::get_main_table(TABLE_MAIN_USER);

// Check based on accesses in track_e_course_access
$sql2 = "SELECT t.c_id, UNIX_TIMESTAMP(MAX(t.login_course_date)) as last_visit
        FROM $tableAccess t, $tableUser u
        WHERE t.user_id = u.id
        AND t.user_id = u.id
        AND u.status = 5
        GROUP BY t.c_id";
$result2 = Database::query($sql2);
$items2 = Database::store_result($result2, 'ASSOC');
$accessUnix = strtotime($access);
$courses = [];
foreach ($items2 as $item) {
    if ($accessUnix > $item['last_visit']) {
        // The course has not been accessed by students since $access
        $courses[$item['c_id']] = $item['last_visit'];
    }
}
$sql = "SELECT
            id, code, directory, creation_date
        FROM $tableCourse
        WHERE id IN (".implode(',', array_keys($courses)).")";
$result = Database::query($sql);
$items = Database::store_result($result, 'ASSOC');

if ($simulate) {
    echo $sql.PHP_EOL;
}
echo "Found ".count($items)." courses matching the given dates.".PHP_EOL;

$total = 0;
$count = 0;
if (!empty($items)) {
    foreach ($items as $item) {
        $size = exec('du -sh '.__DIR__.'/../../app/courses/'.$item['directory']);
        list($mysize, $mypath) = preg_split('/\t/', $size);
        $size = trim($mysize);
        echo "[$count] Course ".$item['code'].'('.$item['id'].') created on '.$item['creation_date'].' and last used on '.date('Y-m-d', $courses[$item['id']]).' uses '.$size.PHP_EOL;
        echo('rm -rf '.__DIR__.'/../../app/courses/'.$item['directory']).PHP_EOL;
        echo('rm -rf '.__DIR__.'/../../app/cache/'.$item['directory'].'_*').PHP_EOL;
        if (!$simulate) {
            CourseManager::delete_course($item['code']);
            // The normal procedure moves the course directory to archive, so
            // delete it there as well
            exec('rm -rf '.__DIR__.'/../../app/courses/'.$item['directory']);
            exec('rm -rf '.__DIR__.'/../../app/cache/'.$item['directory'].'_*');
        }
        // The normal procedure also created a database dump, but it is
        // stored in the course folder, so no issue there...
        $matches = [];
        preg_match('/^(\d+)(\D)$/', $size, $matches);
        switch($matches[2]) {
            case 'K':
                $total += $matches[1];
                break;
            case 'M':
                $total += $matches[1]*1024;
                break;
            case 'G':
                $total += $matches[1]*1024*1024;
                break;
        }
        $count ++;
        if ($count%100 == 0) {
            echo '### Until now: '.$total.'K in '.$count.' courses'.PHP_EOL;
        }
    }
}
echo round($total / (1024)).'MB in '.$count.' courses'.PHP_EOL;
