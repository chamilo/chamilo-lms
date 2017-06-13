<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

if (api_get_configuration_value('allow_career_diagram') == false) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = api_get_js('jsplumb2.js');

$sessions = SessionManager::get_sessions_by_user(api_get_user_id());

$content = '';
$extraFieldValue = new ExtraFieldValue('session');
$extraFieldValueCareer = new ExtraFieldValue('career');
$career = new Career();
foreach ($sessions as $session) {
    $sessionId = $session['session_id'];

    $item = $extraFieldValue->get_values_by_handler_and_field_variable(
        $sessionId,
        'external_career_id'
    );

    if ($item && isset($item['value']) && !empty($item['value'])) {
        $careerId = $item['value'];
        $careerInfo = $career->find($careerId);
        if (!empty($careerInfo)) {
            $itemCareer = $extraFieldValueCareer->get_values_by_handler_and_field_variable(
                $careerId,
                'career_diagram'
            );
            if ($itemCareer && !empty($itemCareer['value'])) {
                $graph = unserialize($itemCareer['value']);
                $content .= Career::renderDiagram($careerInfo, $graph);
            }
        }
    }
}

$view = new Template('');
$view->assign('content', $content);
$view->display_one_col_template();
