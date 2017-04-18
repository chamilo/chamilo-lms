<?php
/* For licensing terms, see /license.txt */
/**
 * Get the intro steps for the web page
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.tour
 */
require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'plugin.class.php';
require_once api_get_path(SYS_PLUGIN_PATH).'tour/src/tour_plugin.class.php';

if (!api_is_anonymous()) {
    $currentPageClass = isset($_GET['page_class']) ? $_GET['page_class'] : '';

    $tourPlugin = Tour::create();

    $json = $tourPlugin->getTourConfig();

    $currentPageSteps = array();

    foreach ($json as $pageContent) {
        if ($pageContent['pageClass'] == $currentPageClass) {
            foreach ($pageContent['steps'] as $step) {
                $currentPageSteps[] = array(
                    'element' => $step['elementSelector'],
                    'intro' => $tourPlugin->get_lang($step['message'])
                );
            }

            break;
        }
    }

    if (!empty($currentPageSteps)) {
        echo json_encode($currentPageSteps);
    }
}
