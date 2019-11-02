<?php
/* For licensing terms, see /license.txt */

$courseCode = isset($_GET['cDir']) ? $_GET['cDir'] : '';
$sessionId = isset($_GET['id_session']) ? $_GET['id_session'] : '';

$url = "../../public/courses/$courseCode?session_id=$sessionId";
header("Location: $url");
exit;
