<?php
/* For licensing terms, see /license.txt */
/**
 * This script contains the code to edit and send an e-mail to one of
 * the platform's users.
 * It can be called from the JavaScript library email_links.lib.php which
 * overtakes the mailto: links to use the internal interface instead.
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 * @author Julio Montoya <gugli100@gmail.com> Updating form with formvalidator
 */

// name of the language file that needs to be included

use \ChamiloSession as Session;

$language_file = array('index', 'admin', 'registration');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

if (empty($_user['user_id'])) {
	api_not_allowed(true);
}

if (empty($_SESSION['origin_url'])) {
	$origin_url = $_SERVER['HTTP_REFERER'];
	Session::write('origin_url',$origin_url);
}

$form = new FormValidator('email_editor', 'post');
$form->addElement('hidden', 'dest');
$form->addElement('text', 'email_address', get_lang('EmailDestination'));
$form->addElement('text', 'email_title', get_lang('EmailTitle'), array('class' => 'span5'));
$form->freeze('email_address');
$form->addElement('textarea', 'email_text', get_lang('EmailText'), array('class' => 'span5', 'rows' => '6'));

$form->addRule('email_address', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email_title', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email_text', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email_address', get_lang('EmailWrong'), 'email');

$form->addElement('button', 'submit', get_lang('SendMail'));
        
$defaults = array(  'dest' => Security::remove_XSS($_REQUEST['dest']),
                    'email_address' => Security::remove_XSS($_REQUEST['dest']),
                    'email_title' => Security::remove_XSS($_POST['email_title']),
                    'email_text' => Security::remove_XSS($_POST['email_text'])
    
);
$form->setDefaults($defaults);

if ($form->validate()) {
    $text = Security::remove_XSS($_POST['email_text'])."\n\n---\n".get_lang('EmailSentFromDokeos')." ".api_get_path(WEB_PATH);
	$email_administrator=Security::remove_XSS($_POST['dest']);
	$user_id=api_get_user_id();
	$title=Security::remove_XSS($_POST['email_title']);
	$content=Security::remove_XSS($_POST['email_text']);
	if (!empty($_user['mail'])) {        
		api_mail_html('',$email_administrator,$title,$text,api_get_person_name($_user['firstname'],$_user['lastname']), $_user['mail']);
		UserManager::send_message_in_outbox ($email_administrator,$user_id,$title, $content);
	} else {
		api_mail_html('',$email_administrator,$title,$text,get_lang('Anonymous'));
	}
	$orig = $_SESSION['origin_url'];
	Session::erase('origin_url');
	header('location:'.$orig);
    exit;
}
Display::display_header(get_lang('SendEmail'));
$form->display();
Display::display_footer();