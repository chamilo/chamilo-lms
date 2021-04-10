<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$fromVue = $_REQUEST['from_vue'] ?? 0;
$vueParam = $fromVue ? '&from_vue=1' : '';

if ('true' == api_get_setting('allow_social_tool') && 'true' == api_get_setting('allow_message_tool')) {
    header('Location:inbox.php?f=social'.$vueParam);
} elseif ('true' == api_get_setting('allow_message_tool')) {
    header('Location:inbox.php?'.$vueParam);
}
exit;
