<?php
/* For licensing terms, see /license.txt */
/*
Call this file to disconnect from CAS session.
logoutWithUrl() not used because with CAS v3 you cannot redirect your logout to a specific URL
because of security reason.
*/
require '../..//inc/global.inc.php';
online_logout($_SESSION['_user']['user_id']);
