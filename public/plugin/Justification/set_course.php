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
$currentCourse = $plugin->getDefaultCourseId();

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
    $url = api_get_path(WEB_PLUGIN_PATH).'Justification/list.php?';
    header('Location: '.$url);
    exit;
}

$actionLinks = Display::toolbarButton(
    $plugin->get_lang('Back'),
    api_get_path(WEB_PLUGIN_PATH).'Justification/list.php',
    'arrow-left',
    'primary'
);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

$content = '
<section class="w-full space-y-6">
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                <span class="mdi mdi-book-cog-outline text-2xl"></span>
            </div>
            <div>
                <h2 class="text-2xl font-semibold text-gray-90">'.$plugin->get_lang('SetNewCourse').'</h2>
                <p class="text-sm text-gray-50">'.$plugin->get_lang('SetNewCourseHelp').'</p>
            </div>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        '.$form->returnForm().'
    </div>
</section>';

$tpl->assign('content', $content);
$tpl->display_one_col_template();
