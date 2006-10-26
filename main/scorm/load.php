<?php
/*
---------------------------------------------------------------------- 
Dokeos - elearning and course management software

Copyright (c) 2004 Dokeos S.A.
Copyright (c) Denes Nagy (darkden@freemail.hu)

For a full list of contributors, see "credits.txt".
The full license can be read in "license.txt".

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

See the GNU General Public License for more details.

Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
---------------------------------------------------------------------- 
*/
/**
============================================================================== 
* This file is the buttons menu that allows going forward-backward in the
* SCORM sequence. It is independent from the current element viewed.
*	@package dokeos.scorm
============================================================================== 

//this file loads in when the whole content is loaded, and does not refresh while in operaion !

*/
//error_log($_SERVER['REQUEST_URI'],0);
$time=time();
$langFile = "scorm";
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

header('Content-Type: text/html; charset='. $charset);

$indexRoute = $_REQUEST['indexRoute'];
$file = $_REQUEST['file'];
// Check if the requested file is in the right location (scorm folder in course directory and no .. in the path)
$file_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/scorm';
if(substr($file,0,strlen($file_path)) != $file_path || strpos($file,'..') > 0)
{
	api_not_allowed();
}
$edoceo = $_REQUEST['edoceo'];
$openDir = $_REQUEST['openDir'];

$backurl = $_REQUEST['backurl'];
$fs = $_REQUEST['fs'];

$who=$_user ['firstName']." ".$_user ['lastName'];

$RequestUri = GetbackURL($_SERVER["REQUEST_URI"]);

?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/scorm.css">
</head>
<body>
<form action="contents.php<?php echo "?file=$file&openDir=".urlencode($openDir)."&edoceo=$edoceo&time=$time"; ?>" method="post" target="contents" name='theform'>
<table cellpadding='0' cellspacing='0' align='left'><tr><td><font color='white'><div align='left' style='margin-left: 0.5em'>

<a href="contents.php?menu=my_status&file=<?php echo "$file&openDir=$openDir&edoceo=$edoceo&time=$time&fs=$fs"; ?>#statustable" target="sco"><img border="0" src="../img/scormstatus.gif" title="<?php echo get_lang('ScormMystatus'); ?>"></a> &nbsp;&nbsp;
<a href="javascript:document.theform.menu.value='prev';document.theform.submit();"><img border="0" src="../img/previous.gif" title="<?php echo get_lang('ScormPrevious'); ?>"></a>
&nbsp;&nbsp;
<a href="javascript:document.theform.menu.value='next';document.theform.submit();"><img border="0" src="../img/next.gif" title="<?php echo get_lang('ScormNext'); ?>"></a>
&nbsp;&nbsp;
<a href="javascript:document.theform.menu.value='restart';document.theform.submit();"><img border="0" src="../img/scormrestart.jpg" title="<?php echo get_lang('ScormRestart'); ?>"></a>
&nbsp;&nbsp;

<?php 
if (isset($_GET["fs"]))
{
	if(strcmp($_GET["fs"],"true")==0)	
	{	
		?>
			<a href="<?php echo $RequestUri."&fs=false"; ?>" target='_top'><img border="0" src="../img/scormexitfullscreen.jpg" title="<?php echo get_lang('ScormExitFullScreen'); ?>"></a>
			&nbsp;&nbsp;
		<?php
	}
	else
	{
		?>
			<a href="<?php echo $RequestUri."&fs=true"; ?>" target='_top'><img border="0" src="../img/scormfullscreen.jpg" title="<?php echo get_lang('ScormFullScreen'); ?>"></a>
		&nbsp;&nbsp;
		<?php
	}
}
else
{
	?>		
			<a href="<?php echo $RequestUri."&fs=true"; ?>" target='_top'><img border="0" src="../img/scormfullscreen.jpg" title="<?php echo get_lang('ScormFullScreen'); ?>"></a>
			&nbsp;&nbsp;
	<?php
}

		// functions 
		
		
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

?>
</font></div></td></tr></table>
<input type='hidden' name='menu' value='init' />
</form>
<?php
api_session_unregister('s_href');
//api_session_unregister('s_identifier');
?>
</body>
</html>