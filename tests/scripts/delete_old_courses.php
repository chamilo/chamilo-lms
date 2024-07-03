<?php
/**
 * This deletes courses that were created but no file was ever uploaded, and
 * that were created previous to a specific date and last used previous to
 * another specific date (see $creation and $access)
 * Use this script with caution, as it will completely remove any trace of the
 * deleted courses.
 * Please note that this is not written with the inclusion of the concept of
 * sessions. As such, it might delete courses but leave the course reference
 * in the session, which would cause issues.
 * Only deletes courses that are considered empty or "only with demo content".
 * Launch from the command line.
 * Usage: php delete_old_courses.php
 */

exit;

require_once '../../main/inc/global.inc.php';

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

// The date before which the course must have been created to be considered
$creation = '2019-06-14';
// The last date at which the course must have been accessed to be considered.
// If it was accessed *after* that date, it will NOT be considered for deletion.
$access = '2019-06-14';

$tableExercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);

$sql = "SELECT
            id, code, directory, creation_date, last_visit
        FROM $tableCourse c
        WHERE creation_date < '$creation' AND (last_visit < '$access' OR last_visit IS NULL) ".
// Set course codes and uncomment the next line to exclude these from deletion
//        " AND c.code NOT IN ('CHAMILOSESSIONS1ES','CHACOBU1ESPRE','CHAPA1ESPRE','CHAPA1FRX','CHACOBU1FRX','CHAPA1ESX','CHACOBU1ESX','DESIGNCHAMILO','EXELEARNING','CHAPA1ENX','CHACOBU1ENX') ".
        " ORDER by code
";
echo $sql.PHP_EOL;

$result = Database::query($sql);
$items = Database::store_result($result, 'ASSOC');
$total = 0;
$count = 0;
if (!empty($items)) {
    foreach ($items as $item) {
        $size = exec('du -sh '.__DIR__.'/../../courses/'.$item['directory']);
        echo "Course ".$item['code'].'('.$item['id'].') created on '.$item['creation_date'].' and last used on '.$item['last_visit'].' uses '.substr($size, 0, 8).PHP_EOL;
        // Check if it's 160K or 9.1M in size, which is the case for 'empty'
        // courses (created without or with example content)
        if (substr($size, 0, 4) == '160K' or substr($size, 0, 4) == '9,1M') {
            CourseManager::delete_course($item['code']);
            // The normal procedure moves the course directory to archive, so
            // delete it there as well
            echo('rm -rf '.__DIR__.'/../../archive/'.$item['directory'].'_*').PHP_EOL;
            exec('rm -rf '.__DIR__.'/../../archive/'.$item['directory'].'_*');
            // The normal procedure also created a database dump, but it is
            // stored in the course folder, so no issue there...
            if (substr($size, 0, 4) == '160K') {
                $total += 160;
            }
            if (substr($size, 0, 4) == '9,1M') {
                $total += 9100;
            }
            $count ++;
            if ($count%100 == 0) {
                // Print progressive information about the freed space
                echo '### Until now: '.$total.'K in '.$count.' courses'.PHP_EOL;
            }
        }
    }
}
// Print final information about the freed space
echo $total.'K in '.$count.' courses'.PHP_EOL;
