<?php
/**
 * This script is included by main/admin/settings.lib.php and generally
 * includes things to execute in the main database (settings_current table)
 * @package chamilo.plugin.bigbluebutton
 */

require_once __DIR__.'/config.php';
BBBPlugin::create()->install();