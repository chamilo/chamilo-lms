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
$lpQuestionInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, []);

$showHeader = (empty($lpItemInfo) && empty($oLp)) && empty($lpQuestionInfo);

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

if (!empty($lpItemInfo)) {
    $plugin->addAttemptInLearningPath(
        $userId,
        $lpItemInfo['lp_item'],
        $lpItemInfo['lp']
    );
} elseif (!empty($lpQuestionInfo)) {
    $plugin->addAttemptInQuiz(
        $userId,
        $lpQuestionInfo['question'],
        $lpQuestionInfo['quiz']
    );
}

if ($userId) {
    $wsid = WhispeakAuthPlugin::getAuthUidValue($userId);

    if (empty($wsid)) {
        $message = Display::return_message($plugin->get_lang('SpeechAuthNotEnrolled'), 'warning');

        if (!empty($lpQuestionInfo)) {
            echo $message;
        } else {
            Display::addFlash($message);
        }

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'whispeakauth/authentify_password.php');

        exit;
    }
}

if (!empty($lpQuestionInfo)) {
    echo api_get_js('rtc/RecordRTC.js');
    echo api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');
} else {
    $htmlHeadXtra[] = api_get_js('rtc/RecordRTC.js');
    $htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');
}

$sampleText = '';

try {
    $sampleText = WhispeakAuthRequest::authenticateSentence($plugin);
} catch (Exception $exception) {
    if ($showHeader) {
        api_not_allowed(
            true,
            Display::return_message($exception->getMessage(), 'error')
        );
    }

    if (!$showHeader && $oLp) {
        api_not_allowed(
            false,
            Display::return_message($exception->getMessage(), 'error')
        );
    }

    WhispeakAuthPlugin::displayNotAllowedMessage($exception->getMessage());
}

ChamiloSession::write(WhispeakAuthPlugin::SESSION_SENTENCE_TEXT, $sampleText);

$template = new Template(
    !$showHeader ? '' : $plugin->get_title(),
    $showHeader,
    $showHeader,
    false,
    true,
    false
);
$template->assign('show_form', $showForm);
$template->assign('sample_text', $sampleText);

$content = $template->fetch('whispeakauth/view/authentify_recorder.html.twig');

if (!empty($lpQuestionInfo)) {
    echo $content;

    exit;
}

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
