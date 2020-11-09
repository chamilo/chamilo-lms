<?php
/* For licensing terms, see /license.txt */
/**
 * Quick form to ask for password reminder.
 *
 * @package chamilo.custompages
 */
require_once api_get_path(SYS_PATH).'main/inc/global.inc.php';
require_once __DIR__.'/language.php';

$template = new Template(get_lang('LostPassword'), false, false, false, false, true, true);

$error = null;

if (isset($content['info']) && !empty($content['info'])) {
    $error = $content['info'];
}
$template->assign('error');
$template->assign('form', $content['form']);
$layout = $template->get_template('custompage/lostpassword.tpl');
$content = $template->fetch($layout);
$template->assign('content', $content);
$template->display_blank_template();
