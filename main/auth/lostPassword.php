<?php

/* For licensing terms, see /license.txt */

/**
 * SCRIPT PURPOSE :.
 *
 * This script allows users to retrieve the password of their profile(s)
 * on the basis of their e-mail address. The password is send via email
 * to the user.
 *
 * Special case : If the password are encrypted in the database, we have
 * to generate a new one.
 *
 * @todo refactor, move relevant functions to code libraries
 */
require_once __DIR__.'/../inc/global.inc.php';

// Custom pages
// Had to move the form handling in here, because otherwise there would
// already be some display output.

// Forbidden to retrieve the lost password
if (api_get_setting('allow_lostpassword') === 'false') {
    api_not_allowed(true);
}

$reset = Request::get('reset');
$userId = Request::get('id');

$this_section = SECTION_CAMPUS;

$tool_name = get_lang('LostPassword');

if ($reset && $userId) {
    $messageText = Login::reset_password($reset, $userId, true);
    Display::addFlash(
        Display::return_message($messageText, 'info', false)
    );
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$form = new FormValidator('lost_password', 'post', '', '', [], FormValidator::LAYOUT_GRID);
$form->addHeader($tool_name);
$form->addText(
    'user',
    [
        get_lang('LoginOrEmailAddress'),
        get_lang('EnterEmailUserAndWellSendYouPassword'),
    ],
    true
);

$captcha = api_get_setting('allow_captcha');
$allowCaptcha = $captcha === 'true';

if ($allowCaptcha) {
    $ajax = api_get_path(WEB_AJAX_PATH).'form.ajax.php?a=get_captcha';
    $options = [
        'width' => 220,
        'height' => 90,
        'callback' => $ajax.'&var='.basename(__FILE__, '.php'),
        'sessionVar' => basename(__FILE__, '.php'),
        'imageOptions' => [
            'font_size' => 20,
            'font_path' => api_get_path(SYS_FONTS_PATH).'opensans/',
            'font_file' => 'OpenSans-Regular.ttf',
            //'output' => 'gif'
        ],
    ];
    $form->setLayout('inline');
    $captcha_question = $form->addElement(
        'CAPTCHA_Image',
        'captcha_question',
        '',
        $options
    );
    $form->addElement('static', null, null, get_lang('ClickOnTheImageForANewOne'));
    $form->addElement('text', 'captcha', get_lang('EnterTheLettersYouSee'), ['size' => 40]);
    $form->addRule('captcha', get_lang('EnterTheCharactersYouReadInTheImage'), 'required', null, 'client');
    $form->addRule('captcha', get_lang('TheTextYouEnteredDoesNotMatchThePicture'), 'CAPTCHA', $captcha_question);
}

$form->addButtonSend(get_lang('Send'), 'submit', false, [], 'btn-block', null);

if ($form->validate()) {
    $values = $form->exportValues();
    $user = Login::get_user_accounts_by_username($values['user']);

    if (!$user) {
        $messageText = get_lang('NoUserAccountWithThisEmailAddress');

        if (CustomPages::enabled() && CustomPages::exists(CustomPages::LOST_PASSWORD)) {
            CustomPages::display(
                CustomPages::LOST_PASSWORD,
                ['info' => $messageText]
            );
            exit;
        }

        Display::addFlash(
            Display::return_message($messageText, 'error', false)
        );
        header('Location: '.api_get_self());
        exit;
    }

    if ('true' === api_get_plugin_setting('whispeakauth', WhispeakAuthPlugin::SETTING_ENABLE)) {
        WhispeakAuthPlugin::deleteEnrollment($user['uid']);
    }

    $passwordEncryption = api_get_configuration_value('password_encryption');

    if ($passwordEncryption === 'none') {
        $messageText = Login::send_password_to_user($user, true);

        if (CustomPages::enabled() && CustomPages::exists(CustomPages::INDEX_UNLOGGED)) {
            CustomPages::display(
                CustomPages::INDEX_UNLOGGED,
                ['info' => $messageText]
            );
            exit;
        }

        Display::addFlash(
            Display::return_message($messageText, 'info', false)
        );
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    if ($user['auth_source'] == 'extldap') {
        Display::addFlash(
            Display::return_message(get_lang('CouldNotResetPasswordBecauseLDAP'), 'info', false)
        );
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    $userResetPasswordSetting = api_get_setting('user_reset_password');

    if ($userResetPasswordSetting === 'true') {
        $userObj = api_get_user_entity($user['uid']);
        Login::sendResetEmail($userObj);

        if (CustomPages::enabled() && CustomPages::exists(CustomPages::INDEX_UNLOGGED)) {
            CustomPages::display(
                CustomPages::INDEX_UNLOGGED,
                ['info' => get_lang('CheckYourEmailAndFollowInstructions')]
            );
            exit;
        }

        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    $messageText = Login::handle_encrypted_password($user, true);

    if (CustomPages::enabled() && CustomPages::exists(CustomPages::INDEX_UNLOGGED)) {
        CustomPages::display(
            CustomPages::INDEX_UNLOGGED,
            ['info' => $messageText]
        );
        exit;
    }

    Display::addFlash(
        Display::return_message($messageText, 'info', false)
    );
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

if (CustomPages::enabled() && CustomPages::exists(CustomPages::LOST_PASSWORD)) {
    CustomPages::display(
        CustomPages::LOST_PASSWORD,
        ['form' => $form->returnForm()]
    );
    exit;
}

$tpl = new Template(null);
$tpl->assign('content', $form->toHtml());
$tpl->display_one_col_template();
