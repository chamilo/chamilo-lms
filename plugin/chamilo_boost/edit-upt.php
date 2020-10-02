<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once 'inc/functions.php';
require_once 'inc/boost-form.php';
require_once 'boostTitle.php';

api_protect_admin_script();

$plugin = boostTitle::create();

$awp = api_get_path(WEB_PATH);

$content = '<span style="font-size:9px;" >Assistant de mise à jour du '.'06/07/2020'.'</span><br>';

$content .= '<iframe style="border:none;" frameborder="0" width="450" height="450" src="cham-update/process-update.php" ></iframe>';

$tpl = new Template('');

$tpl->assign('form', $content);

$content = $tpl->fetch('/chamilo_boost/view/options-v09.tpl');

// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
