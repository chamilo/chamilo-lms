<?php
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
*	@package dokeos.scorm
============================================================================== 
*/

$langFile = "scormdocument";
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

api_session_register('items');
echo "<html><head><title>".get_lang('ScormBuilder')." - Dokeos</title>";
?>
<script type='text/javascript'>
/* <![CDATA[ */
function gonnadie() {
	if (window.opener && !window.opener.closed) {
		opener.document.theform.starter.value='dead';
	}
}
function aliveagain() {
	opener.document.theform.starter.value='alive';
	window.resizeTo(screen.width-250,screen.height-70-112);
	window.moveTo(200,200);
	alert('onload happened in new window');
}
/* ]]> */
</script>
</head>
<?php
if ($open=='builderwindow') { //opening the new browsing window and saving the title of Path
	echo "<frameset rows='27,100%' frameborder='yes' framespacing='1' onunload='javascript:gonnadie()' onload='javascript:aliveagain()'>",
	 "	<frame name='builderheader' scrolling='no' src='scormbuilderstarter.php'>",
	 "  <frame name='buildernet' src='http://www.google.com'>",
	 "</frameset>";							 //$rootWeb/index.php (Dokeos)
	// UNDER CONSTRUCTION -----------------------------------------------------
	//$result = api_sql_query("INSERT INTO `$TBL_SCORM_MAIN` VALUES ('".$index."','".$defaultorgtitle."','".$_course['official_code']."')");
	// UNDER CONSTRUCTION END -------------------------------------------------
} else {
		 //opening the normal Dokeos window
		echo "<frameset rows='112,*' frameborder='yes' framespacing='1' onload='javascript:window.resizeTo(screen.width-200,screen.height-25)'>",
			//if you put this : onload='javascript:window.resizeTo(screen.width-600,screen.height-50)' then
		    //the original Dokeos window will be resized
		 "  <frame name='top' scrolling='no' target='contents' src='scormbuilderheader.php'>",
	     "  <frameset cols='245,100%' frameborder='yes' framespacing='1'>",
		 "	  <frameset rows='143,*' frameborder='no' framespacing='0'>",
		 "       <frame name='toc1' scrolling='no' src='scormbuilderadditem.php'>",
		 "       <frame name='toc2' scrolling='no' src='scormbuilderbrowse.php'>",
		 "    </frameset>",
		 "	  <frame name='net' scrolling='auto'";
		echo ">",
		 "</frameset>";
}
?>
	<noframes>
	  <body>
	  <p>This page uses frames, but your browser doesn't support them.
		If you cannot use a more modern browser, please contact us to ask for a non-frames version.
	  </p>
	  </body>
	</noframes>
</html>
