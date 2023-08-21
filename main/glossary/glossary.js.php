<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

header('Content-type: application/x-javascript');

$origin = api_get_origin();

$tpl = new Template('', false, false, false, true, false, false);

$glossaryInDocs = api_get_setting('show_glossary_in_documents');
$glossaryExtraTools = api_get_setting('show_glossary_in_extra_tools');

$showGlossary = false;
if ('learnpath' == $origin) {
    $showGlossary = in_array($glossaryExtraTools, ['lp', 'exercise_and_lp']);
} elseif (isset($glossaryInDocs) && 'none' != $glossaryInDocs) {
    $showGlossary = true;
}

if ($showGlossary) {
    $templateName = 'glossary/glossary_auto.js.tpl';
    if ('ismanual' == $glossaryInDocs) {
        $templateName = 'glossary/glossary_manual.js.tpl';
    }
    $addReady = isset($_GET['add_ready']) ? true : false;
    $tpl->assign('add_ready', $addReady);
    $contentTemplate = $tpl->get_template($templateName);
    $tpl->display($contentTemplate);
}
