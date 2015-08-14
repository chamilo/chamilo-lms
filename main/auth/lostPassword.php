<?php
/* For licensing terms, see /license.txt */
/**
 * SCRIPT PURPOSE :
 *
 * This script allows users to retrieve the password of their profile(s)
 * on the basis of their e-mail address. The password is send via email
 * to the user.
 *
 * Special case : If the password are encrypted in the database, we have
 * to generate a new one.
*
*	@todo refactor, move relevant functions to code libraries
*
*	@package chamilo.auth
*/

require_once '../inc/global.inc.php';

// Custom pages
// Had to move the form handling in here, because otherwise there would
// already be some display output.
global $_configuration;

if (CustomPages::enabled()) {
    // Reset Password when user goes to the link
    if (isset($_GET['reset']) && $_GET['reset'] &&
        isset($_GET['id']) && $_GET['id']
    ) {
        $mesg = Login::reset_password($_GET["reset"], $_GET["id"], true);
        CustomPages::display(CustomPages::INDEX_UNLOGGED, array('info' => $mesg));
    }

    // Check email/username and do the right thing
    if (isset($_POST['user'])) {
        $usersRelatedToUsername = Login::get_user_accounts_by_username($_POST['user']);

        if ($usersRelatedToUsername) {
            $by_username = true;
            foreach ($usersRelatedToUsername as $user) {
                if ($_configuration['password_encryption'] != 'none') {
                    Login::handle_encrypted_password($user, $by_username);
                } else {
                    Login::send_password_to_user($user, $by_username);
                }
            }
        } else {
            CustomPages::display(
                CustomPages::LOST_PASSWORD,
                array('error' => get_lang('NoUserAccountWithThisEmailAddress'))
            );
        }
    } else {
        CustomPages::display(CustomPages::LOST_PASSWORD);
    }

    CustomPages::display(
        CustomPages::INDEX_UNLOGGED,
        array('info' => get_lang('YourPasswordHasBeenEmailed'))
    );
}

$tool_name = get_lang('LostPassword');

$this_section = SECTION_CAMPUS;
$tool_name = get_lang('LostPass');

// Forbidden to retrieve the lost password
if (api_get_setting('allow_lostpassword') == 'false') {
	api_not_allowed(true);
}

$formToString = '';
if (isset($_GET['reset']) && isset($_GET['id'])) {
    $message = Display::return_message(
        Login::reset_password($_GET["reset"], $_GET["id"], true),
        'normal',
        false
    );
	$message .= '<a href="'.api_get_path(WEB_CODE_PATH).'auth/lostPassword.php" class="btn btn-back" >'.get_lang('Back').'</a>';
	Display::addFlash($message);
} else {
	$form = new FormValidator('lost_password');
    $form->addElement('header', $tool_name);
    $form->addElement(
        'text',
        'user',
        array(
            get_lang('LoginOrEmailAddress'),
            get_lang('EnterEmailUserAndWellSendYouPassword'),
        ),
        array('size' => '40')
    );
	$form->addButtonSend(get_lang('Send'));

	// Setting the rules
	$form->addRule('user', get_lang('ThisFieldIsRequired'), 'required');

	if ($form->validate()) {
		$values = $form->exportValues();

        $usersRelatedToUsername = Login::get_user_accounts_by_username(
            $values['user']
        );

		if ($usersRelatedToUsername) {
            $by_username = true;
            foreach ($usersRelatedToUsername as $user) {
                if ($_configuration['password_encryption'] != 'none') {
                    $setting = api_get_setting('user_reset_password');
                    if ($setting === 'true') {
                        $userObj = Database::getManager()->getRepository('ChamiloUserBundle:User')->find($user['uid']);
                        Login::sendResetEmail($userObj);
                    } else {
                        $message = Login::handle_encrypted_password($user, $by_username);
                        Display::addFlash($message);
                    }
                } else {
                    $message = Login::send_password_to_user($user, $by_username);
                    Display::addFlash($message);
                }
            }
		} else {
            Display::addFlash(
                Display::return_message(
                    get_lang('NoUserAccountWithThisEmailAddress'),
                    'warning'
                )
            );
		}
	} else {
		$formToString = $form->returnForm();
	}
}


$controller = new IndexManager($tool_name);
$controller->set_login_form();
$tpl = $controller->tpl;
$tpl->assign('form', $formToString);

$template = $tpl->get_template('auth/lost_password.tpl');
$tpl->display($template);
