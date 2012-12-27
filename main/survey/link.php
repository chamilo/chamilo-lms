<?php

require_once '../inc/global.inc.php';

if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

$survey_id = isset($_REQUEST['l']) ? intval($_REQUEST['l']) : null;

if (empty($survey_id)) {
    api_not_allowed(true);
}

//Display::display_header(get_lang('Survey'), 'Survey');

if (!survey_manager::survey_generation_hash_available()) {
    api_not_allowed(true);
}
$course_info  = api_get_course_info_by_id($_REQUEST['c']);

$hash_is_valid = survey_manager::validate_survey_hash($_REQUEST['l'], $_REQUEST['c'], $_REQUEST['s'], $_REQUEST['g'], $_REQUEST['h']);
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
        //echo Display::url(get_lang('Go'), $link, array('class' => 'btn btn-primary btn-large'));
        //echo ' '.Display::url(get_lang('Regenerate'), $link, array('class' => 'btn btn-primary btn-large'));
        //echo "<pre>$link</pre>";
    }
} else {
    api_not_allowed(true);

}