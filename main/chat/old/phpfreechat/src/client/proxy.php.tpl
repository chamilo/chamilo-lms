<?php

// gzip compression should not be used because it can slowdown a lot the page loading (>60 seconds!)
ini_set('zlib.output_compression','Off');

ob_start(); // start capturing output

$rootpath    = dirname(__FILE__)."/../../";

$allowedpath = array();
//%allowedpath%

// get the wanted file path
// and check if the file is allowed to be displayed
$page = isset($_GET["p"]) ? $_GET["p"] : "";
if ($page == "") die();
$files = array();
foreach($allowedpath as $ap)
{
  $f = realpath($ap."/".$page);
  if ($f !== FALSE && file_exists($f)) $files[] = $f;
}
$found = "";
for( $i = 0; $i < count($allowedpath) && $found == ""; $i++)
{
  $ap = $allowedpath[$i];
  foreach($files as $file)
  {
    if (strpos($file, $ap) === 0 ) $found = $file;
  }
}
if (trim($found) == "")
  die();
else
  $file = $found;

// setup the HTTP cache
// @todo understand how it really works
session_cache_limiter('public');

// output the file content
readfile($file);

// output HTTP headers
$contenttype   = "text/plain";
//$contentlength = filesize($file);
if (preg_match("/\.js$/", $file))
  $contenttype = "text/javascript";
else if (preg_match("/\.css$/", $file))
  $contenttype = "text/css";
else if (preg_match("/\.gif$/", $file))
  $contenttype = "image/gif";
else if (preg_match("/\.jpg$/", $file))
  $contenttype = "image/jpeg";
else if (preg_match("/\.jpeg$/", $file))
  $contenttype = "image/jpeg";
else if (preg_match("/\.png$/", $file))
  $contenttype = "image/png";
header("Content-Type: ".$contenttype);
$contentlength = ob_get_length();
header("Content-Length: ".$contentlength);

// As far as I can tell the only way to mimic ob_flush()'s behaviour on PHP < 4.2.0 is calling ob_end_flush() followed by ob_start().
// http://fr.php.net/manual/en/function.ob-flush.php#28477
ob_end_flush();
ob_start();

?>
