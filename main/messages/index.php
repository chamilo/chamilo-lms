<?php
/* For licensing terms, see /chamilo_license.txt */
require_once '../inc/global.inc.php';
if (api_get_setting('allow_social_tool')=='true' &&  api_get_setting('allow_message_tool')=='true') {
	header('Location:inbox.php?f=social');	
} elseif ( api_get_setting('allow_message_tool')=='true') {
	header('Location:inbox.php');
}
?>