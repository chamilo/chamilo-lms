<?php
/* For licensing terms, see /vendor/license.txt */

/**
 * This script is included by main/admin/settings.lib.php when unselecting a plugin
 * and is meant to remove things installed by the install.php script in both
 * the global database and the courses tables.
 *
 * @package chamilo.plugin.clockworksms
 *
 * @author  Imanol Losada <imanol.losada@beeznest.com>
 */
require_once __DIR__.'/config.php';
ClockworksmsPlugin::create()->uninstall();
