<?php
/* For licensing terms, see /license.txt */

require_once '../main/inc/global.inc.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$url = Certificate::getCertificatePublicURL($id);
header("Location: $url");
exit;