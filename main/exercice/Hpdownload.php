<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/


/**
*	This script shows the list of exercises for administrators and students.
*	@package dokeos.exercise
* 	@author Istvan Mandak
* 	@version $Id: Hpdownload.php 20555 2009-05-12 14:01:40Z juliomontoya $
*/


session_cache_limiter('public');

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include(api_get_path(LIBRARY_PATH)."events.lib.inc.php");

$tbl_document = Database::get_course_table(TABLE_DOCUMENT);

$doc_url=str_replace(array('../','\\..','\\0'),array('','',''),urldecode($_GET['doc_url']));
$filename=basename($doc_url);

// launch event
//event_download($doc_url);
if (isset($_course['path'])) {
	$full_file_name = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.Security::remove_XSS($doc_url);
} else {
	$full_file_name = api_get_path(SYS_COURSE_PATH).$cid.'/document'.Security::remove_XSS($doc_url);
}

if(!is_file($full_file_name)) {
	exit();
}

$extension=explode('.',$filename);
$extension=strtolower($extension[sizeof($extension)-1]);

switch($extension) {
	case 'gz':		$content_type='application/x-gzip';			break;
	case 'zip':		$content_type='application/zip';			break;
	case 'pdf':		$content_type='application/pdf';			break;
	case 'png':		$content_type='image/png';					break;
	case 'gif':		$content_type='image/gif';					break;
	case 'jpg':		$content_type='image/jpeg';					break;
	case 'txt':		$content_type='text/plain';					break;
	case 'htm':		$content_type='text/html';					break;
	case 'html':	$content_type='text/html';					break;
	default:		$content_type='application/octet-stream';	break;
}

header('Content-disposition: filename='.$filename);
header('Content-Type: '.$content_type);
header('Expires: '.gmdate('D, d M Y H:i:s',time()+10).' GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s',time()+10).' GMT');

/*
------------------------------------------------------------------------------
	Dynamic parsing section
	is activated whenever a user views an html file
	work in progress
	- question: we could also parse per line,
	perhaps this would be faster.
	($file_content = file($full_file_name) returns file in array)
------------------------------------------------------------------------------
*/

if($content_type == 'text/html') {
	include (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
	$directory_name = dirname($full_file_name);

	$dir=str_replace(array('\\',$_configuration['root_sys']."courses/".$_course['path'].'/document'),array('/',''),$directory_name);

	if($dir[strlen($dir)-1] != '/')
	{
		$dir.='/';
	}


	//Parse whole file at one
	$fp = fopen($full_file_name, "r");
	$file_content = fread ($fp, filesize ($full_file_name));
	fclose($fp);
	//$file_content = api_replace_parameter($dir, $file_content, "src");
	//$file_content = api_replace_parameter($dir, $file_content, "href");

	/*
	//parse line per line
	$file_content_array = file($full_file_name);

	foreach($file_content_array as $line)
	{
		$line = api_replace_parameter($dir, $line, "src");
		$line = api_replace_parameter($dir, $line, "href");
		$file_content .= $line;
	}
	*/


		$exercicePath = api_get_self();
  	$exfile = explode('/',$exercicePath);
  	$exfile = $exfile[sizeof($exfile)-1];
  	$exercicePath = substr($exercicePath,0,strpos($exercicePath,$exfile));
  	$exercicePath = $exercicePath;

		$content = $file_content;
		$mit = "function Finish(){";

		$js_content = "var SaveScoreVariable = 0; // This variable included by Dokeos System\n".
		"function mySaveScore() // This function included by Dokeos System\n".
"{\n".
"   if (SaveScoreVariable==0)\n".
"		{\n".
"			SaveScoreVariable = 1;\n".
"			if (C.ie)\n".
"			{\n".
"				document.location.href = \"".$exercicePath."savescores.php?origin=$origin&time=$time&test=".$doc_url."&uid=".$_user['user_id']."&cid=".$cid."&score=\"+Score;\n".
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
		$newcontent = str_replace($mit,$js_content,$content);

		$prehref="javascript:void(0);";
		$posthref=$_configuration['root_web']."main/exercice/Hpdownload.php?doc_url=".$doc_url."&cid=".$cid."&uid=".$uid;
		$newcontent = str_replace($prehref,$posthref,$newcontent);


		$prehref="class=\"GridNum\" onclick=";
		$posthref="class=\"GridNum\" onMouseover=";
		$newcontent = str_replace($prehref,$posthref,$newcontent);


		header('Content-length: '.strlen($newcontent));
		// Dipsp.
		echo $newcontent;

	exit();
}

//normal case, all non-html files
//header('Content-length: '.filesize($full_file_name));
$fp=fopen($full_file_name,'rb');
fpassthru($fp);
fclose($fp);
?>