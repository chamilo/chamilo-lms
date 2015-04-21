<?php
/* For licensing terms, see /license.txt */

require_once '../../../global.inc.php';

$moreButtonsInMaximizedMode = false;

if (api_get_setting('more_buttons_maximized_mode') == 'true') {
    $moreButtonsInMaximizedMode = true;
}

$template = new Template();
$template->assign('moreButtonsInMaximizedMode', $moreButtonsInMaximizedMode);
$template->display('default/javascript/editor/ckeditor/config_js.tpl');

