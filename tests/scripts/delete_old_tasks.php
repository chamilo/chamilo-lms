<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes previous tasks from disk to clear space.
 * Configure the date on lines 22-23 to change the dates after/before which 
 * to delete.
 * This works based on sessions dates (it will not delete tasks from 
 * base courses).
 * This script should be located inside the tests/scripts/ folder to work
 * @author Paul Patrocinio <ppatrocino@icpna.edu.pe>
 * @author Percy Santiago <psantiago@icpna.edu.pe>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
exit(); //remove this line to execute from the command line
if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require __DIR__.'/../../main/inc/conf/configuration.php';

// Dates
$expiryDate = '2015-06-01'; //session start date must be < to be considered
$fromDate = '2011-01-01'; //session start date must be > to be considered

$sessionCourses = array();
$coursesCodes = array();
$coursesDirs = array();
if (!$conexion = mysql_connect($_configuration['db_host'], $_configuration['db_user'], $_configuration['db_password'])) {
    echo 'Could not connect to database';
    exit;
}

if (!mysql_select_db($_configuration['main_database'], $conexion)) {
    echo 'Could not select database '.$_configuration['main_database'];
    exit;
}
echo "[".time()."] Querying sessions\n";
$sql = "SELECT id FROM session where access_start_date < '$expiryDate' AND access_start_date > '$fromDate'";

$res = mysql_query($sql, $conexion);
if ($res === false) {
    //die("Error querying sessions: ".Database::error($res)."\n");
}

$countSessions = mysql_num_rows($res);
$sql = "SELECT count(*) FROM session";
$resc = mysql_query($sql, $conexion);
if ($resc === false) {
    //die("Error querying sessions: ".Database::error($res)."\n");
}
$countAllSessions = mysql_result($resc, 0, 0);
echo "[".time()."] Found $countSessions sessions between $fromDate and $expiryDate on a total of $countAllSessions sessions."."\n";

while ($session = mysql_fetch_assoc($res)) {
    $sql2 = "SELECT c.id AS cid, c.code as ccode, c.directory as cdir
            FROM course c, session_rel_course s 
            WHERE s.id_session = ".$session['id']." 
            AND s.course_code = c.code";
    $res2 = mysql_query($sql2, $conexion); //Database::query($sql2);
    
    if ($res2 === false) {
        die("Error querying courses for session ".$session['id'].": ".mysql_error($res2)."\n");
    }

    if (mysql_num_rows($res2) > 0) {
        while ($course = mysql_fetch_assoc($res2)) {
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
            AND url LIKE '%ALP%'";
	
    $resCarpetas = mysql_query($sql, $conexion); //Database::query($sql);

	if (mysql_num_rows($resCarpetas) > 0) {
        while ($rowDemo = mysql_fetch_assoc($resCarpetas)) {

            $carpetaAlpElimina = $_configuration['root_sys'].'courses/'.$coursesDirs[$cid].'/work'.$rowDemo['url'];
            
            //echo "rm -rf ".$carpetaAlpElimina."\n";
            $size = folderSize($carpetaAlpElimina);
            $totalSize += $size;
            echo "Freeing $size of a total $totalSize bytes in $carpetaAlpElimina\n";
            exec('rm -rf '.$carpetaAlpElimina);
        }
		
	$sqldel = "DELETE FROM c_student_publication
		WHERE
		c_id = $cid
		AND session_id = $sid AND active = 1;";
        $resdel = mysql_query($sqldel);
        if ($resdel === false) {
            echo "Error querying sessions: ".Database::error($resdel)."\n";
        }
    } 
}
echo "[".time()."] Deleted tasks from $countSessions sessions between $fromDate and $expiryDate on a total of $countAllSessions sessions."."\n";

/**
 * Helper function to calculate size of a folder
 * @author See php.net comments on filesize()
 */
function folderSize($dir) {
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
