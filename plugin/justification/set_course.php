<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$tool = 'justification';
$plugin = Justification::create();

$tpl = new Template($tool);
$fields = [];

$form = new FormValidator('add');
$form->addHeader($plugin->get_lang('SetNewCourse'));
$currentCourse = api_get_setting('justification_default_course_id', 'justification');

if (!empty($currentCourse)) {
    $courseInfo = api_get_course_info_by_id($currentCourse);
    Display::addFlash(Display::return_message(get_lang('Course').': '.$courseInfo['title']));
}

$form->addSelectAjax(
    'course_id',
    get_lang('Course'),
    null,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
    ]
);
$form->addButtonSave(get_lang('Save'));

if ($form->validate()) {
    $values = $form->getSubmitValues();
    api_set_setting('justification_default_course_id', $values['course_id']);
    Display::addFlash(Display::return_message(get_lang('Saved')));
    $url = api_get_path(WEB_PLUGIN_PATH).'justification/list.php?';
    header('Location: '.$url);
    exit;
}

$actionLinks = Display::toolbarButton(
    $plugin->get_lang('Back'),
    api_get_path(WEB_PLUGIN_PATH).'justification/list.php',
    'arrow-left',
    'primary'
);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

$content = $form->returnForm();

$tpl->assign('content', $content);
$tpl->display_one_col_template();
