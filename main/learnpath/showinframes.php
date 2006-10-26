<?php // $Id: showinframes.php 9246 2006-09-25 13:24:53Z bmol $ 
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
*	This is a copy of showinframes.php, with modifications for the learning path.
*
*	@package dokeos.learnpath
*	@todo do not show an empty frame first, show the first document in the learning path as first step 
============================================================================== 
*/

// including the learnpath language variable
$langFile = "learnpath";

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

echo "<html><head><title>".get_lang('_learning_path')." - Dokeos</title></head>";

session_unregister('cur_open');
unset($cur_open);
session_unregister('prevofficedoc');
unset($prevofficedoc);
session_unregister('openmethod');
unset($openmethod);

$source_id = $_REQUEST['source_id'];
$action = $_REQUEST['action'];
$learnpath_id = $_REQUEST['learnpath_id'];
$chapter_id = $_REQUEST['chapter_id'];
$originalresource = $_REQUEST['originalresource'];
$fs = $_REQUEST['fs'];

$backurl = getURL(api_get_path(WEB_PATH)).$_SERVER['REQUEST_URI'];

if (isset($_GET["fs"]) && (strcmp($_GET["fs"],"true")==0) )	
{		
	?>
	<frameset rows="0,50,0,*,70,0" frameborder="no" framespacing="0">			
	<frame name="top" scrolling="no" target="contents" src="headerpage.php?<?php echo " source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no";?>">
			<frame name="menu" scrolling="no" src="menu.php?<?php echo "source_id=5&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no&backurl=$backurl&fs=true";?>">
			<frame name='toc' scrolling='auto' src="contents.php?<?php echo "openfirst=yes&source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no";?>">
			<frame name="content" scrolling="auto" src="blank.php?display_msg=1">
			<frame name="message" scrolling="no" src="blank.php">
			<frame src="UntitledFrame-3" name="hidden">
	</frameset>
	<?php
}
else
{
	?>
	<frameset rows="100,*" frameborder="no" framespacing="0">

	<frame name="top" scrolling="no" target="contents" src="headerpage.php?<?php echo " source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no";?>">
	
	<frameset cols="220,*" frameborder="1" framespacing="2" border=1>
	<frameset rows="*,39,70,0" frameborder="no" framespacing="0">
		<frame name='toc' scrolling='auto' src="contents.php?<?php echo "openfirst=yes&source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no";?>">
		<frame name="menu" scrolling="no" src="menu.php?<?php echo "source_id=5&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no&backurl=$backurl";?>">
		<frame name="message" scrolling="no" src="blank.php">
		<frame name="hidden">
	</frameset>

	<frame name="content" scrolling="auto" src="blank.php?display_msg=1">
	</frameset>
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