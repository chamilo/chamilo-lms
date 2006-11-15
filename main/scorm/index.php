<?php
/**
============================================================================== 
*	DEPRECATED
*	@package dokeos.scorm
============================================================================== 
*/
/*
---------------------------------------------------------------------- 

!!!!!!!!!!!!!!!    NO LONGER IN USE !!!!!!!!!!!!!!!!!!!!!!!!!!

---------------------------------------------------------------------- 
*/

$time=time();
$langFile = "scorm";
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

if ($_user['user_id']=='') 
{ //that means, that the used logged out in the other window
	echo "<script type=\"text/javascript\">
			/* <![CDATA[ */
			alert('".get_lang('ScormLoggedout')."');
			window.close();
			/* ]]> */
		  </script>";
	exit();
}
echo "<html><head><title>".get_lang('ScormTitle')."</title></head>"; ?>


<frameset rows="40,*" frameborder="yes" framespacing="1">
  <frameset cols="500,*,0,0,0" frameborder="yes" framespacing="1">
    <frame name="load" src="load.php<?php echo "?file=$file&openDir=$openDir&time=$time"; ?>" scrolling="no">
    <frame name="message" scrolling="no">
  <frame name="API" src="scormfunctions.php<?php echo "?$time"; ?>"> <!-- Scorm 1.2 contents search for this -->
  <frame name="API_1484_11" src="scormfunctions.php<?php echo "?$time"; ?>">  <!-- Scorm 1.3 contents search for this -->
  <frame name="hidden3">
  </frameset>
  <frameset cols="270,100%" frameborder="yes" framespacing="1">
    <frame name='contents' scrolling='auto' src="contents.php<?php echo "?file=$file&openDir=$openDir&time=$time"; ?>">";
    <frame name="sco" scrolling="auto">
  </frameset>
</frameset>
<noframes><body bgcolor="#FFFFFF">
Your browser cannot handle frames !
</body></noframes>
</html>