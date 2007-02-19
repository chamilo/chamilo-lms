<?php
require_once('../../../../../global.inc.php');
require_once('../../../../security.lib.php');

$filename = urldecode(stripslashes($_GET['file']));

//prevent download of something outside of the course dir
$course_dir   = $_course['path']."/document";
$course_path = api_get_path(SYS_COURSE_PATH).$course_dir;
$in_course = Security::check_abs_path($filename,$course_path);
if(!$in_course){
	$filename = "";
}

// required for IE, otherwise Content-disposition is ignored
if(ini_get('zlib.output_compression'))
  ini_set('zlib.output_compression', 'Off');

$file_extension = strtolower(substr(strrchr($filename,"."),1));

if( $filename == "" ) 
{
  echo "<html><title>Download Script</title><body><font color=red>ERROR: download file NOT SPECIFIED.</font></body></html>";
  exit;
} elseif ( ! file_exists( $filename ) ) 
{
  echo "<html><title>Download Script</title><body><font color=red>ERROR: File not found.</font></body></html>";
  exit;
};
switch( $file_extension )
{
  case "pdf": $ctype="application/pdf"; break;
  case "exe": $ctype="application/octet-stream"; break;
  case "zip": $ctype="application/zip"; break;
  case "doc": $ctype="application/msword"; break;
  case "xls": $ctype="application/vnd.ms-excel"; break;
  case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
  case "gif": $ctype="image/gif"; break;
  case "png": $ctype="image/png"; break;
  case "jpeg":
  case "jpg": $ctype="image/jpg"; break;
  default: $ctype="application/force-download";
}
header("Pragma: public"); // required
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false); // required for certain browsers 
header("Content-Type: $ctype");
// change, added quotes to allow spaces in filenames, by Rajkumar Singh
header("Content-Disposition: attachment; filename=\"".basename($filename)."\";" );
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".filesize($filename));
readfile("$filename");
exit();
?>