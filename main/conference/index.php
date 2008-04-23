<?php
/* See license terms in /dokeos_license.txt */
/**
 * Generates the HTML page containing the iframe that loads the videoconference's Flash plugin
 */
include("../inc/global.inc.php");
api_protect_course_script();
$_SESSION["roomType"] = $_GET['type'];
?>
<span align="center">
<iframe frameborder="0" scrolling="no" width="100%" height="100%" src="videoconference.php"></iframe>
</span>