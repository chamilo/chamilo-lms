<?php

/* For licensing terms, see /license.txt */
/**
 * This script shows the list of exercises for administrators and students.
 *
 * @author Istvan Mandak
 *
 * @version $Id: Hpdownload.php 22201 2009-07-17 19:57:03Z cfasanando $
 */
session_cache_limiter('public');

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$this_section = SECTION_COURSES;

$tbl_document = Database::get_course_table(TABLE_DOCUMENT);

$doc_url = str_replace(['../', '\\..', '\\0', '..\\'], ['', '', '', ''], urldecode($_GET['doc_url']));
$filename = basename($doc_url);

// launch event
//Event::event_download($doc_url);
if (isset($_course['path'])) {
    $course_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
    $full_file_name = $course_path.Security::remove_XSS($doc_url);
} else {
    $course_path = api_get_path(SYS_COURSE_PATH).$cid.'/document';
    $full_file_name = $course_path.Security::remove_XSS($doc_url);
}

if (!is_file($full_file_name)) {
    exit;
}

if (!Security::check_abs_path($full_file_name, $course_path.'/')) {
    exit;
}

$extension = explode('.', $filename);
$extension = strtolower($extension[count($extension) - 1]);

switch ($extension) {
    case 'gz':
        $content_type = 'application/x-gzip';
        break;
    case 'zip':
        $content_type = 'application/zip';
        break;
    case 'pdf':
        $content_type = 'application/pdf';
        break;
    case 'png':
        $content_type = 'image/png';
        break;
    case 'gif':
        $content_type = 'image/gif';
        break;
    case 'jpg':
        $content_type = 'image/jpeg';
        break;
    case 'txt':
        $content_type = 'text/plain';
        break;
    case 'htm':
        $content_type = 'text/html';
        break;
    case 'html':
        $content_type = 'text/html';
        break;
    default:
        $content_type = 'application/octet-stream';
        break;
}

header('Content-disposition: filename='.$filename);
header('Content-Type: '.$content_type);
header('Expires: '.gmdate('D, d M Y H:i:s', time() + 10).' GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', time() + 10).' GMT');

/*
    Dynamic parsing section
    is activated whenever a user views an html file
    work in progress
    - question: we could also parse per line,
    perhaps this would be faster.
    ($file_content = file($full_file_name) returns file in array)
*/

if ($content_type == 'text/html') {
    $directory_name = dirname($full_file_name);
    $coursePath = api_get_path(SYS_COURSE_PATH);
    $dir = str_replace(['\\', $coursePath.$_course['path'].'/document'], ['/', ''], $directory_name);

    if ($dir[strlen($dir) - 1] != '/') {
        $dir .= '/';
    }

    //Parse whole file at one
    $fp = fopen($full_file_name, "r");
    $file_content = fread($fp, filesize($full_file_name));
    fclose($fp);
    $exercisePath = api_get_self();
    $exfile = explode('/', $exercisePath);
    $exfile = $exfile[sizeof($exfile) - 1];
    $exercisePath = substr($exercisePath, 0, strpos($exercisePath, $exfile));

    $content = $file_content;
    $mit = "function Finish(){";
    $js_content = "var SaveScoreVariable = 0; // This variable included by Chamilo\n".
        "function mySaveScore() // This function included by Chamilo\n".
"{\n".
"   if (SaveScoreVariable==0)\n".
"		{\n".
"			SaveScoreVariable = 1;\n".
"			if (C.ie)\n".
"			{\n".
"				document.location.href = \"".$exercisePath."savescores.php?origin=$origin&time=$time&test=".$doc_url."&uid=".$_user['user_id']."&cid=".$cid."&score=\"+Score;\n".
"				//window.alert(Score);\n".
"			}\n".
"			else\n".
"			{\n".
"			}\n".
"		}\n".
"}\n".
"// Must be included \n".
"function Finish(){\n".
" mySaveScore();";
    $newcontent = str_replace($mit, $js_content, $content);

    $prehref = "javascript:void(0);";
    $posthref = api_get_path(WEB_CODE_PATH)."main/exercise/Hpdownload.php?doc_url=".$doc_url."&cid=".$cid."&uid=".$uid;
    $newcontent = str_replace($prehref, $posthref, $newcontent);
    $prehref = "class=\"GridNum\" onclick=";
    $posthref = "class=\"GridNum\" onMouseover=";
    $newcontent = str_replace($prehref, $posthref, $newcontent);
    header('Content-length: '.strlen($newcontent));
    // Dipsp.
    echo $newcontent;
    exit();
}

//normal case, all non-html files
//header('Content-length: ' . filesize($full_file_name));
$fp = fopen($full_file_name, 'rb');
fpassthru($fp);
fclose($fp);
