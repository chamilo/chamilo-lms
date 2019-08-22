<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = WhispeakAuthPlugin::create();

$plugin->protectTool();

$userId = ChamiloSession::read(WhispeakAuthPlugin::SESSION_2FA_USER, 0) ?: api_get_user_id();

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

if (empty($userId)) {
    api_not_allowed($showFullPage);
}

if (!empty($lpQuestionInfo) && empty($lpItemInfo)) {
    echo Display::return_message(
        $plugin->get_lang('MaxAttemptsReached').'<br>'
        .'<strong>'.$plugin->get_lang('LoginWithUsernameAndPassword').'</strong>',
        'warning',
        false
    );
}

$form = new FormValidator(
    'form-login',
    'POST',
    api_get_path(WEB_PLUGIN_PATH).'whispeakauth/ajax/authentify_password.php',
    null,
    null,
    FormValidator::LAYOUT_BOX_NO_LABEL
);
$form->addElement(
    'password',
    'password',
    get_lang('Pass'),
    ['id' => 'password', 'icon' => 'lock fa-fw', 'placeholder' => get_lang('Pass')]
);
$form->addHidden('sec_token', '');
$form->setConstants(['sec_token' => Security::get_token()]);
$form->addButton('submitAuth', get_lang('LoginEnter'), 'check', 'primary', 'default', 'btn-block');

$template = new Template(
    !$showFullPage ? '' : $plugin->get_title(),
    $showFullPage,
    $showFullPage,
    false,
    true,
    false
);
$template->assign('form', $form->returnForm());

$content = $template->fetch('whispeakauth/view/authentify_password.html.twig');

if (!empty($lpQuestionInfo) && empty($lpItemInfo)) {
    echo $content;

    exit;
}

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
