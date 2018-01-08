<?php
/* For license terms, see /license.txt */
/**
 * Configuration script for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSession = $plugin->get('include_sessions') === 'true';
$includeServices = $plugin->get('include_services') === 'true';

api_protect_admin_script(true);

Display::addFlash(
    Display::return_message(
        get_lang('Info').' - '.$plugin->get_lang('CoursesInSessionsDoesntDisplayHere'),
        'info'
    )
);

$courses = $plugin->getCoursesForConfiguration();

// breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'buycourses/index.php',
    'name' => $plugin->get_lang('plugin_title')
];

$templateName = $plugin->get_lang('AvailableCourses');

$tpl = new Template($templateName);

$tpl->assign('product_type_course', BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
$tpl->assign('product_type_session', BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
$tpl->assign('courses', $courses);
$tpl->assign('sessions_are_included', $includeSession);
$tpl->assign('services_are_included', $includeServices);

if ($includeSession) {
    $sessions = $plugin->getSessionsForConfiguration();
    $tpl->assign('sessions', $sessions);
}

if ($includeServices) {
    $services = $plugin->getServices();
    $tpl->assign('services', $services);
}

$content = $tpl->fetch('buycourses/view/configuration.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
