<?php
/* See license terms in /license.txt */
/**
 * Generates the HTML page containing the iframe that loads the videoconference's Flash plugin
 */
require_once '../inc/global.inc.php'; 
api_protect_course_script();
//Not sure what values can be set here I just found that $_SESSION["roomType"] could be the string "conference" 
if ($_GET['type'] == 'conference') {
	$_SESSION["roomType"] = $_GET['type'];
}
?>
<span align="center">
<iframe frameborder="0" scrolling="no" width="100%" height="100%" src="videoconference.php"></iframe>
</span>