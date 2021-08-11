<?php

/**
 * This script is included by main/admin/settings.lib.php when unselecting a plugin
 * and is meant to remove things installed by the install.php script in both
 * the global database and the courses tables.
 *
 * @package chamilo.plugin.pens
 */
/**
 * Queries.
 */
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/lib/pens.php';
PENSPlugin::create()->uninstall();
