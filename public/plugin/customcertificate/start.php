<?php
/**
 * This script initiates a customcertificate plugin.
 *
 * @package chamilo.plugin.customcertificate
 */
$course_plugin = 'customcertificate';
require_once __DIR__.'/config.php';

$plugin = CustomCertificatePlugin::create();
$enable = $plugin->get('enable_plugin_customcertificate') == 'true';

if ($enable) {
    if (api_is_platform_admin() || api_is_teacher()) {
        $url = 'src/index.php?';
        $url .= (isset($_GET['cidReq']) ? api_get_cidreq() : 'default=1');
        header('Location: '.$url);
        exit;
    } else {
        $session = api_get_session_entity(api_get_session_id());
        $_course = api_get_course_info();
        $webCoursePath = api_get_path(WEB_COURSE_PATH);
        $url = $webCoursePath.$_course['path'].'/index.php'.($session ? '?id_session='.$session->getId() : '');

        Display::addFlash(
            Display::return_message($plugin->get_lang('OnlyAdminPlatform'))
        );

        header('Location: '.$url);
        exit;
    }
} else {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}
