<?php
/**
 * This script initiates a notebookteacher plugin.
 */
$course_plugin = 'notebookteacher';
require_once __DIR__.'/config.php';

$plugin = NotebookTeacherPlugin::create();

if (!$plugin->isEnabled(true)) {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}

if (api_is_allowed_to_edit(false, true) || api_is_drh()) {
    $url = 'src/index.php?'.api_get_cidreq();
    header('Location: '.$url);
    exit;
}

Display::addFlash(
    Display::return_message($plugin->get_lang('ToolForTeacher'), 'warning')
);

$url = api_get_course_url(api_get_course_int_id(), api_get_session_id(), api_get_group_id());

if (empty($url)) {
    $url = api_get_path(WEB_PATH);
}

header('Location: '.$url);
exit;
