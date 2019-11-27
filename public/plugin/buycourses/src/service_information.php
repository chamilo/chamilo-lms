<?php
/* For licensing terms, see /license.txt */

/**
 * Service information page
 * Show information about a service (for custom purposes).
 *
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 *
 * @package chamilo.buycourses_plugin
 */
$cidReset = true;

require_once '../../../main/inc/global.inc.php';

$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : false;
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(
        WEB_PLUGIN_PATH
    ).'buycourses/resources/css/style.css"/>';
$plugin = BuyCoursesPlugin::create();

$includeServices = $plugin->get('include_services') === 'true';

if (!$includeServices) {
    api_not_allowed(true);
}

$service = $plugin->getService($serviceId);

if (!$service['id']) {
    api_not_allowed(true);
}

$template = new Template(false);
$template->assign('pageUrl', api_get_path(WEB_PATH)."service/{$serviceId}/information/");
$template->assign('service', $service);
$template->assign('essence', Essence\Essence::instance());

$content = $template->fetch('buycourses/view/service_information.tpl');

$template->assign('content', $content);
$template->display_one_col_template();
