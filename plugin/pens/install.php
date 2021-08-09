<?php
/**
 * This script is included by main/admin/settings.lib.php and generally
 * includes things to execute in the main database (settings_current table).
 *
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization.
 */
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/lib/pens.php';
PENSPlugin::create()->install();
