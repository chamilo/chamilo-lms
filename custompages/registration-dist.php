<?php
/* For licensing terms, see /license.txt */
/**
 * This script allows for specific registration rules (see CustomPages feature of Chamilo)
 * Please contact CBlue regarding any licences issues.
 * Author: noel@cblue.be
 * Copyright: CBlue SPRL, 20XX (GNU/GPLv3).
 *
 * @package chamilo.custompages
 */
require_once api_get_path(SYS_PATH).'main/inc/global.inc.php';
require_once __DIR__.'/language.php';

$template = new Template(get_lang('Registration'), false, false, false, false, true, true);

/**
 * Removes some unwanted elementend of the form object.
 * 03-26-2020  Added check if element exist.
 */
if (isset($content['form']->_elementIndex['extra_mail_notify_invitation'])) {
    $content['form']->removeElement('extra_mail_notify_invitation');
}
if (isset($content['form']->_elementIndex['extra_mail_notify_message'])) {
    $content['form']->removeElement('extra_mail_notify_message');
}
if (isset($content['form']->_elementIndex['extra_mail_notify_group_message'])) {
    $content['form']->removeElement('extra_mail_notify_group_message');
}
$content['form']->removeElement('official_code');
$content['form']->removeElement('phone');

$template->assign('form', $content['form']->returnForm());
$layout = $template->get_template('custompage/registration.tpl');
$content = $template->fetch($layout);
$template->assign('content', $content);
$template->display_blank_template();
