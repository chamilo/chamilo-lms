<?php
/* For licensing terms, see /license.txt */

$courseCode = isset($_GET['cDir']) ? $_GET['cDir'] : '';
$sessionId = isset($_GET['id_session']) ? $_GET['id_session'] : '';

$url = "../../public/courses/$courseCode?id_session=$sessionId";
header("Location: $url");
exit;
