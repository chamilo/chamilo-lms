<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$tpl = new Template();

$templateName = 'glossary/glossary_auto.js.tpl';
if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
    $templateName = 'glossary/glossary_manual.js.tpl';
}

$addReady = isset($_GET['add_ready']) ? true : false;
$tpl->assign('add_ready', $addReady);
$contentTemplate = $tpl->get_template($templateName);
header('Content-type: application/x-javascript');
$tpl->display($contentTemplate);
