<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = WhispeakAuthPlugin::create();

$plugin->protectTool();

$userId = ChamiloSession::read(WhispeakAuthPlugin::SESSION_2FA_USER, 0) ?: api_get_user_id();
$showForm = !$userId;

/** @var array $lpItemInfo */
$lpItemInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_LP_ITEM, []);
/** @var learnpath $oLp */
$oLp = ChamiloSession::read('oLP', null);
/** @var array $lpQuestionInfo */
$lpQuestionInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, []);
/** @var Exercise $objExercise */
$objExercise = ChamiloSession::read('objExercise', null);

$isAuthOnLp = !empty($lpItemInfo) && !empty($oLp);
$isAuthOnQuiz = !empty($lpQuestionInfo) && !empty($objExercise);

$showFullPage = !$isAuthOnLp && !$isAuthOnQuiz;

if (ChamiloSession::read(WhispeakAuthPlugin::SESSION_AUTH_PASSWORD, false)) {
    ChamiloSession::erase(WhispeakAuthPlugin::SESSION_AUTH_PASSWORD);

    $message = Display::return_message(
        $plugin->get_lang('MaxAttemptsReached').'<br>'
        .'<strong>'.$plugin->get_lang('LoginWithUsernameAndPassword').'</strong>',
        'warning'
    );

    if (empty($lpQuestionInfo)) {
        Display::addFlash($message);
    }

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'whispeakauth/authentify_password.php');

    exit;
}

if ($userId) {
    $wsid = WhispeakAuthPlugin::getAuthUidValue($userId);

    if (empty($wsid)) {
        $message = Display::return_message($plugin->get_lang('SpeechAuthNotEnrolled'), 'warning');

        if (!empty($lpQuestionInfo) && empty($lpItemInfo)) {
            echo $message;
        } else {
            Display::addFlash($message);
        }

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'whispeakauth/authentify_password.php');

        exit;
    }
}

if (!empty($lpQuestionInfo) && empty($lpItemInfo)) {
    echo api_get_js('rtc/RecordRTC.js');
    echo api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');
} else {
    $htmlHeadXtra[] = api_get_js('rtc/RecordRTC.js');
    $htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');
}

$sampleText = 'Hola, mundo';

ChamiloSession::write(WhispeakAuthPlugin::SESSION_SENTENCE_TEXT, $sampleText);

$template = new Template(
    !$showFullPage ? '' : $plugin->get_title(),
    $showFullPage,
    $showFullPage,
    false,
    true,
    false
);
$template->assign('show_form', $showForm);
$template->assign('sample_text', $sampleText);

$content = $template->fetch('whispeakauth/view/authentify_recorder.html.twig');

if (!empty($lpQuestionInfo) && empty($lpItemInfo)) {
    echo $content;

    exit;
}

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
