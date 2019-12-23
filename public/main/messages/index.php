<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
if ('true' == api_get_setting('allow_social_tool') && 'true' == api_get_setting('allow_message_tool')) {
    header('Location:inbox.php?f=social');
} elseif ('true' == api_get_setting('allow_message_tool')) {
    header('Location:inbox.php');
}
exit;
