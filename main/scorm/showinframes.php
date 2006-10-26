<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2005 Dokeos S.A.
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
*	This is the copy of showinframes.php, with little modifications for the scorm player.
*
*	Based on showincontext.php, this file will show documents in a separate frame.
*	
*	@author Denes Nagy
*	@package dokeos.scorm
============================================================================== 
*/

$langFile = "scormdocument";
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$openfirst = $_REQUEST['openfirst'];
$indexRoute = $_REQUEST['indexRoute'];
$file = $_REQUEST['file'];
$openDir = $_REQUEST['openDir'];
// Check if the requested file is in the right location (scorm folder in course directory and no .. in the path)
$file_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/scorm';
if(substr($file,0,strlen($file_path)) != $file_path || strpos($file,'..') > 0)
{
	api_not_allowed();
}
echo "<html><head><title>".get_lang('Doc')." - Dokeos</title></head>";

$path=api_get_path(WEB_COURSE_PATH).api_get_course_path().'/scorm'.$openDir;
//if we find the given text in file (=imsmanifest.xml), then the content is an e-doceo contentm so
// API frames are not to be implemented
$filename=$path.'/imsmanifest.xml';
$edoceo='no';
// introducing the brand new... file check! ;-)
if (!is_file($filename)){
	// is this AICC?
}else{
  //detect if AICC
	$handle = fopen($filename, "rb");
	$contents = '';
	while (!feof($handle)) {
	  $contents .= fread($handle, 8192);
	}
	fclose($handle);
	
	$needle="Made with elearning maker";
	$pos=strpos($contents, $needle);
	if ($pos > 0) { $edoceo="yes"; } else { $edoceo="no"; }
}

$time=time();

$backurl = getURL(api_get_path(WEB_PATH)).$_SERVER['REQUEST_URI'];



if ((isset($_GET["fs"])) && (strcmp($_GET["fs"],"true")==0))  //fullscreen

{
	?>

<frameset rows="0,27,0,*,70,0,0" frameborder="no" framespacing="0">

  <frame name="top" scrolling="no" target="contents" src="headerpage.php?openDir=<?php echo urlencode($openDir); ?>">
  <frame name="load" src="load.php<?php echo "?file=".urlencode($file)."&openDir=".urlencode($openDir)."&time=$time&backurl=$backurl&fs=true&edoceo=$edoceo"; ?>" scrolling="no">
  <frame name='contents' scrolling='auto' src="contents.php<?php echo 	"?file=".urlencode($file)."&openDir=".urlencode($openDir)."&time=$time&openfirst=$openfirst&edoceo=$edoceo"; ?>">
  <frame src="UntitledFrame-12" name="sco" scrolling="auto">
  <frame name="message" scrolling="no" src="blank.php">
  
  <?php if (empty($indexRoute)) { ?>


<?php if ($edoceo=="no") { ?>
			  <frame name="API" src="scormfunctions.php<?php echo "?$time"; ?>"> <!-- Scorm 1.2 contents search for this -->
			  <frame name="API_1484_11" src="scormfunctions.php<?php echo "?$time"; ?>">  <!-- Scorm 1.3 contents search for this -->
<?php } else { ?>
			  <frame name="edoceo1" src="blank.php">
			  <frame name="edoceo2" src="blank.php">
<?php }  ?>

  <?php } else {  //indexRoute exists -> Plantyn content  ?>

  <frame name='contents' scrolling='auto' src="<?php echo "$path/$indexRoute"; ?>">
  <frame name="Untitled" src="blank.php">

  <?php } ?>

</frameset>



<?php
	} else {  //not fullscreen
?>

<frameset rows="100,*" frameborder="no" framespacing="0">

  <frame name="top" scrolling="no" target="contents" src="headerpage.php?openDir=<?php echo urlencode($openDir); ?>">
  
  <?php if (empty($indexRoute)) { ?>

  <frameset cols="220,*" frameborder="1" framespacing="2" border="1">
	<frameset rows="*,27,70" frameborder="no" framespacing="0">
		<frame name='contents' scrolling='auto' src="contents.php<?php echo "?file=".urlencode($file)."&openDir=".urlencode($openDir)."&time=$time&openfirst=$openfirst&edoceo=$edoceo"; ?>">
		<frameset cols="0,0,275" frameborder="no" framespacing="0">
<?php if ($edoceo=="no") { ?>
			  <frame name="API" src="scormfunctions.php<?php echo "?$time"; ?>"> <!-- Scorm 1.2 contents search for this -->
			  <frame name="API_1484_11" src="scormfunctions.php<?php echo "?$time"; ?>">  <!-- Scorm 1.3 contents search for this -->
<?php } else { ?>
			  <frame name="edoceo1" src="blank.php">
			  <frame name="edoceo2" src="blank.php">
<?php }  ?>
    	      <frame name="load" src="load.php<?php echo "?file=".urlencode($file)."&openDir=".urlencode($openDir)."&time=$time&backurl=$backurl&edoceo=$edoceo"; ?>" scrolling="no">
		</frameset>
		<frame name="message" scrolling="no" src="blank.php">
    </frameset>

	<frame name="sco" scrolling="auto">
  </frameset>

  <?php } else {  //indexRoute exists -> Plantyn content  ?>

  <frame name='contents' scrolling='auto' src="<?php echo "$path/$indexRoute"; ?>">

  <?php } ?>

</frameset>


<?php
	}
?>

	<noframes>
	  <body>
	  <p>This page uses frames, but your browser doesn't support them.
		If you cannot use a more modern browser, please contact us to ask for a non-frames version.
	  </p>
	  </body>
	</noframes>
	</frameset>
</html>
