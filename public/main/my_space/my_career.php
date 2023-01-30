<?php
/* For licensing terms, see /license.txt */

use Fhaculty\Graph\Graph;

require_once __DIR__.'/../inc/global.inc.php';

if (false == api_get_configuration_value('allow_career_diagram')) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = api_get_js('jsplumb2.js');

$sessionCategories = UserManager::get_sessions_by_category(api_get_user_id(), false);

$content = '';
$extraFieldValue = new ExtraFieldValue('session');
$extraFieldValueCareer = new ExtraFieldValue('career');
$career = new Career();
foreach ($sessionCategories as $category) {
    $sessions = $category['sessions'];
    foreach ($sessions as $session) {
        $sessionId = $session['session_id'];
        // Getting session extra field 'external_career_id'
        $item = $extraFieldValue->get_values_by_handler_and_field_variable(
            $sessionId,
            'external_career_id'
        );
        if ($item && isset($item['value']) && !empty($item['value'])) {
            // External career id
            $externalCareerId = $item['value'];
            // Getting career id from external career id
            $itemCareer = $extraFieldValueCareer->get_item_id_from_field_variable_and_field_value(
                'external_career_id',
                $externalCareerId
            );
            if ($itemCareer && !empty($itemCareer['item_id'])) {
                $careerId = $itemCareer['item_id'];
                $careerInfo = $career->find($careerId);
                if (!empty($careerInfo)) {
                    $diagram = $extraFieldValueCareer->get_values_by_handler_and_field_variable(
                        $careerId,
                        'career_diagram'
                    );
                    if ($diagram && !empty($diagram['value'])) {
                        /** @var Graph $graph */
                        $graph = UnserializeApi::unserialize('career', $diagram['value']);
                        $content .= Career::renderDiagram($careerInfo, $graph);
                    }
                }
            }
        }
    }
}

$view = new Template('');
$view->assign('content', $content);
$view->display_one_col_template();
