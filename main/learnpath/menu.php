<?php // $Id: menu.php 9246 2006-09-25 13:24:53Z bmol $ 
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Denes Nagy (darkden@freemail.hu)
	
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
*	Show the menu of a learning path.
*
*	@version  v 0.1
*	@access   public
*	@author   Denes Nagy <darkden@freemail.hu>
*	@package dokeos.learnpath
============================================================================== 
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/ 
$time=time();
$langFile = "learnpath";
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

header('Content-Type: text/html; charset='. $charset);

$source_id = $_REQUEST['source_id'];
$action = $_REQUEST['action'];
$learnpath_id = $_REQUEST['learnpath_id'];
$chapter_id = $_REQUEST['chapter_id'];
$originalresource = $_REQUEST['originalresource'];
$backurl = $_REQUEST['backurl'];
$fs = $_REQUEST['fs'];

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/ 

// get backurl from $_SERVER["REQUEST_URI"]
function GetbackURL($path)
{
	$url = explode('backurl=',$path);
	$url = explode('showinframes.php?',$url[1]);
	return ClearbackURL("showinframes.php?".$url[1]);
}	

// cut "&fs=true"
function ClearbackURL($path)
{
	$url = explode('&fs=',$path);
	return $url[0];
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
?>

<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/scorm.css">

</head>
<body>
<form action="contents.php<?php echo "?source_id=5&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no#$learnpath_id"; ?>" method="post" target="toc" name='theform'>
<table cellpadding='0' cellspacing='0' align='left'><tr height='5'><td><img src='../img/scormline.jpg'></td></tr>
<tr><td><div align='left' style='margin-left:0.5em'><font color='white'>

<?php 
$RequestUri = GetbackURL($_SERVER["REQUEST_URI"]);
?>

<a href="contents.php?menu=my_status&<?php echo "source_id=5&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no&fs=$fs"; ?>#statustable" target="content"><img border="0" src="../img/scormstatus.gif" title="<?php echo get_lang('LearnpathMystatus'); ?>"></a> 
&nbsp;&nbsp;
<a href="javascript:document.theform.menu.value='prev';document.theform.submit();"><img border="0" src="../img/previous.gif" title="<?php echo get_lang('LearnpathPrevious'); ?>"></a>
&nbsp;&nbsp;
<a href="javascript:document.theform.menu.value='next';document.theform.submit();"><img border="0" src="../img/next.gif" title="<?php echo get_lang('LearnpathNext'); ?>"></a>
&nbsp;&nbsp;
<a href="javascript:document.theform.menu.value='restart';document.theform.submit();"><img border="0" src="../img/scormrestart.jpg" title="<?php echo get_lang('LearnpathRestart'); ?>"></a>
&nbsp;&nbsp;
<?php 
if (isset($_GET["fs"]))
{
	if(strcmp($_GET["fs"],"true")==0)	
	{	
		?>
			<a href="<?php echo $RequestUri."&fs=false"; ?>" target='_top'><img border="0" src="../img/scormexitfullscreen.jpg" title="<?php echo get_lang('LearnpathExitFullScreen'); ?>"></a>
			&nbsp;&nbsp;
		<?php
	}
	else
	{
		?>
			<a href="<?php echo $RequestUri."&fs=true"; ?>" target='_top'><img border="0" src="../img/scormfullscreen.jpg" title="<?php echo get_lang('LearnpathFullScreen'); ?>"></a>
		&nbsp;&nbsp;
		<?php
	}
}
else
{
	?>		
		<a href="<?php echo $RequestUri."&fs=true"; ?>" target='_top'><img border="0" src="../img/scormfullscreen.jpg" title="<?php echo get_lang('LearnpathFullScreen'); ?>"></a>
		&nbsp;&nbsp;
	<?php
}
?>
</font></div></td></tr><tr><td><img src='../img/scormline.jpg'></td></tr></table>
<input type='hidden' name='menu' value='init'>
</form>

</body></html>