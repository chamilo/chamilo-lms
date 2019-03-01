<?php
/* For licensing terms, see /license.txt */

require_once '../main/inc/global.inc.php';

/**
 * Redirects "courses/ABC/document/my_file.html" to
 * "public/courses/ABCa0d/document/my_file.html"
 * That route uses the Symfony ResourceController see:
 * /src/CoreBundle/Controller/ResourceController.php.
 */
$publicPath = api_get_path(WEB_PUBLIC_PATH);
// http://localhost/chamilo2/courses/ABC/document/aa.html
$courseCode = Security::remove_XSS($_GET['courseCode']);
$path = Security::remove_XSS($_GET['url']);
$type = Security::remove_XSS($_GET['type'] ?? 'show');
$url = $publicPath."courses/$courseCode/document/$path?type=$type";
header("Location: $url");
exit;
