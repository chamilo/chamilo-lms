<?php
/* For licensing terms, see /license.txt */

$courseCode = isset($_GET['cDir']) ? htmlentities($_GET['cDir']) : '';
$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : '';

$url = "../../public/courses/$courseCode?sid=$sessionId";
header("Location: $url");
exit;
