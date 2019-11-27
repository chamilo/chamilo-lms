<?php
/* For licensing terms, see /license.txt */

/**
 * Get the intro steps for the web page.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.tour
 */
require_once __DIR__.'/../../../main/inc/global.inc.php';

if (!api_is_anonymous()) {
    $currentPageClass = isset($_GET['page_class']) ? $_GET['page_class'] : '';
    $tourPlugin = Tour::create();
    $json = $tourPlugin->getTourConfig();
    $currentPageSteps = [];
    foreach ($json as $pageContent) {
        if ($pageContent['pageClass'] == $currentPageClass) {
            foreach ($pageContent['steps'] as $step) {
                $currentPageSteps[] = [
                    'element' => $step['elementSelector'],
                    'intro' => $tourPlugin->get_lang($step['message']),
                ];
            }

            break;
        }
    }

    if (!empty($currentPageSteps)) {
        echo json_encode($currentPageSteps);
    }
}
