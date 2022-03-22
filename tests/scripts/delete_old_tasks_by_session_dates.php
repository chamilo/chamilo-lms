<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes previous tasks from disk to clear space.
 * Configure the date on line 22 to change the date before which to delete,
 * then delete the exit() statement at line 13.
 * This works based on sessions dates.
 * This script should be located inside the tests/scripts/ folder to work
 * @author Paul Patrocinio <ppatrocino@icpna.edu.pe>
 * @author Percy Santiago <psantiago@icpna.edu.pe>
 * @author Yannick Warnier <yannick.warnier@beeznest.com> - Cleanup and debug
 */
die();
require __DIR__.'/../../main/inc/global.inc.php';
$fromDate = '2017-03-01'; //session start date must be > to be considered
$expiryDate = '2022-01-01'; //session end date must be < to be considered
$simulate = false;
$taskNameFilter = ''; // fill with any value to only delete tasks that contain this text
if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}
echo PHP_EOL."Usage: php ".basename(__FILE__)." [options]".PHP_EOL;
echo "Where [options] can be ".PHP_EOL;
echo "  -s         Simulate execution - Do not delete anything, just show numbers".PHP_EOL.PHP_EOL;
echo "Processing...".PHP_EOL.PHP_EOL;

if (!empty($argv[1]) && $argv[1] == '-s') {
    $simulate = true;
    echo "Simulation mode is enabled".PHP_EOL;
}

$sessionCourses = array();
$coursesCodes = array();
$coursesDirs = array();
echo "[".time()."] Querying sessions\n";
$sql = "SELECT id FROM session where access_start_date < '$expiryDate' AND access_start_date > '$fromDate'";

$res = Database::query($sql);

if ($res === false) {
    die("Error querying sessions\n");
}

$countSessions = Database::num_rows($res);
$sql = "SELECT count(*) nbr FROM session";
$resc = Database::query($sql);
if ($resc === false) {
    die("Error querying total sessions\n");
}
$countAllSessions = Database::result($resc, 0, 'nbr');
echo "[".time()."]"
    ." Found $countSessions sessions between $fromDate and $expiryDate on a total of $countAllSessions sessions."."\n";

while ($session = Database::fetch_assoc($res)) {
    $sql2 = "SELECT c.id AS cid, c.code AS ccode, c.directory AS cdir
            FROM course c, session_rel_course s
            WHERE s.session_id = ".$session['id']."
            AND s.c_id = c.id";
    $res2 = Database::query($sql2);

    if ($res2 === false) {
        die("Error querying courses for session ".$session['id']."\n");
    }

    if (Database::num_rows($res2) > 0) {
        while ($course = Database::fetch_assoc($res2)) {
            $sessionCourses[$session['id']] = $course['cid'];
            //$_SESSION['session_course'] = $sessionCourses;

            if (empty($coursesCodes[$course['cid']])) {
                $coursesCodes[$course['cid']] = $course['ccode'];
            }
            if (empty($coursesDirs[$course['cid']])) {
                $coursesDirs[$course['cid']] = $course['cdir'];
            }
        }
    }
}
echo "[".time()."] Filled courses arrays. Now checking tasks...\n";
/**
 * Locate and destroy the expired tasks
 */
//$sessionCourse = $_SESSION['session_course'];

$totalSize = 0;
foreach ($sessionCourses as $sid => $cid) {
    // Check if a folder already exists in this session
    // Folders are exclusive to sessions. If a folder already exists in
    // another session, you will not be allowed to create the same folder in
    // another session. As such, folders belong to one and only one session.
    $sql = "SELECT id, url FROM c_student_publication
            WHERE filetype = 'folder'
            AND c_id = $cid
            AND session_id = $sid
            AND active = 1
            AND url LIKE '%$taskNameFilter%'";

    $resCarpetas = Database::query($sql);

    if (Database::num_rows($resCarpetas) > 0) {
        while ($rowDemo = Database::fetch_assoc($resCarpetas)) {

            $removableFolder = api_get_path(SYS_COURSE_PATH).$coursesDirs[$cid].'/work'.$rowDemo['url'];

            //echo "rm -rf ".$removableFolder."\n";
            $size = folderSize($removableFolder);
            $totalSize += $size;
            echo "Freeing $size summing to a total $totalSize bytes in $removableFolder\n";
            if ($simulate == false) {
                exec('rm -rf '.$removableFolder);
            }
        }

        if ($simulate == false) {
            $sqldel = "
                DELETE FROM c_student_publication
                WHERE c_id = $cid
                AND session_id = $sid AND active = 1;
            ";
            $resdel = Database::query($sqldel);

            if ($resdel === false) {
                echo "Error querying sessions\n";
            }
        }
    }
}
echo "[".time()."] ".($simulate ? "Would delete" : "Deleted")
    ." tasks from $countSessions sessions between $fromDate and $expiryDate on a total of $countAllSessions"
    ." sessions for a total estimated size of "
    .round($totalSize / (1024 * 1024))." MB."."\n";

function folderSize($dir)
{
    $size = 0;
    $contents = glob(rtrim($dir, '/').'/*', GLOB_NOSORT);
    foreach ($contents as $contents_value) {
        if (is_file($contents_value)) {
            $size += filesize($contents_value);
        } else {
            $size += folderSize($contents_value);
        }
    }

    return $size;
}
