<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

if (!function_exists('buycourses_require_enabled_plugin')) {
    function buycourses_require_enabled_plugin(): BuyCoursesPlugin
    {

       $isEnabled = Container::getPluginHelper()->isPluginEnabled('BuyCourses');

        $plugin = BuyCoursesPlugin::create();

        if (!$isEnabled) {
            api_not_allowed(true);
        }

        return $plugin;
    }
}
