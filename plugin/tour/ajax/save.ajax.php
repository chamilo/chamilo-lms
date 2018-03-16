<?php
/* For licensing terms, see /license.txt */
/**
 * Get the intro steps for the web page.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.tour
 */
/**
 * Init.
 */
require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../config.php';

if (!api_is_anonymous()) {
    $currentPageClass = isset($_POST['page_class']) ? $_POST['page_class'] : '';

    if (!empty($currentPageClass)) {
        $userId = api_get_user_id();

        $tourPlugin = Tour::create();
        $tourPlugin->saveCompletedTour($currentPageClass, $userId);
    }
}
