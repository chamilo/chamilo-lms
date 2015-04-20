<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$tpl = new Template();

$templateName = 'glossary/glossary_auto.js.tpl';
if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
    $templateName = 'glossary/glossary_manual.js.tpl';
}

$contentTemplate = $tpl->get_template($templateName);
$tpl->display($contentTemplate);
