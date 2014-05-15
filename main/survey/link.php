<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$survey_id = isset($_REQUEST['i']) ? intval($_REQUEST['i']) : null;

if (empty($survey_id)) {
    api_not_allowed(true);
}
if (!survey_manager::survey_generation_hash_available()) {
    api_not_allowed(true);
}
$course_info  = api_get_course_info_by_id($_REQUEST['c']);

$hash_is_valid = survey_manager::validate_survey_hash($survey_id, $_REQUEST['c'], $_REQUEST['s'], $_REQUEST['g'], $_REQUEST['h']);
if ($hash_is_valid && $course_info) {
    $survey_data = survey_manager::get_survey($survey_id, null, $course_info['code']);

    $invitation_code = api_get_unique_id();

    $params = array(
            'c_id' => $_REQUEST['c'],
            'session_id' => $_REQUEST['s'],
            'user' => $invitation_code,
            'survey_code' => $survey_data['code'],
            'invitation_code' => $invitation_code,
            'invitation_date' => api_get_utc_datetime()
    );
    $invitation_id = SurveyUtil::save_invitation($params);

    if ($invitation_id) {
        $link = api_get_path(WEB_CODE_PATH).'survey/fillsurvey.php?invitationcode='.$invitation_code.'&course='.$course_info['code'];
        header('Location: '.$link);
        exit;
    }
} else {
    api_not_allowed(true);
}
