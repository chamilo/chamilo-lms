<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';
$returnURL = 'start.php?cId='.api_get_course_id().'&sessionId='.api_get_session_id();
include "meeting.php";
