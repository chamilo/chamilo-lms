<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$origin = api_get_origin();

$tpl = new Template();

$glossaryExtraTools = api_get_setting('show_glossary_in_extra_tools');

if ($origin == 'learnpath') {
    $showGlossary = in_array($glossaryExtraTools, ['lp', 'exercise_and_lp']);
} else {
    $showGlossary = in_array($glossaryExtraTools, ['true', 'lp', 'exercise_and_lp']);
}

if ($showGlossary) {
    $templateName = 'glossary/glossary_auto.js.tpl';
    if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
        $templateName = 'glossary/glossary_manual.js.tpl';
    }

    $addReady = isset($_GET['add_ready']) ? true : false;
    $tpl->assign('add_ready', $addReady);
    $contentTemplate = $tpl->get_template($templateName);
    header('Content-type: application/x-javascript');
    $tpl->display($contentTemplate);
}
