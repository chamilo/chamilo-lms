<?php
/**
 * This script initiates a notebookteacher plugin.
 *
 * @package chamilo.plugin.notebookteacher
 */
$course_plugin = 'notebookteacher';
require_once __DIR__.'/config.php';

$plugin = NotebookTeacherPlugin::create();
$enable = $plugin->get('enable_plugin_notebookteacher') == 'true';

if ($enable) {
    if (api_is_teacher() || api_is_drh()) {
        $url = 'src/index.php?'.api_get_cidreq();
        header('Location: '.$url);
        exit;
    } else {
        $session = api_get_session_entity(api_get_session_id());
        $_course = api_get_course_info();
        $webCoursePath = api_get_path(WEB_COURSE_PATH);
        $url = $webCoursePath.$_course['path'].'/index.php'.($session ? '?id_session='.$session->getId() : '');

        Display::addFlash(
            Display::return_message($plugin->get_lang('ToolForTeacher'))
        );

        header('Location: '.$url);
        exit;
    }
} else {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}
