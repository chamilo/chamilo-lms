<?php
/* For licensing terms, see /license.txt */

require_once '../main/inc/global.inc.php';

/**
 * Redirects "courses/ABC/document/my_file.html" to the symfony Resourcecontroller see:
 * src/CoreBundle/Controller/ResourceController.php.
 */
$publicPath = api_get_path(WEB_PUBLIC_PATH);
// http://localhost/chamilo2/courses/ABC/document/aa.html
$courseCode = $_GET['courseCode'];
$path = $_GET['url'];
$url = $publicPath."courses/$courseCode/document/$path";
header("Location: $url");
exit;
