<?php
/**
 * This script is included by main/admin/settings.lib.php and generally 
 * includes things to execute in the main database (settings_current table)
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization
 */

require_once dirname(__FILE__).'/config.php';
BBBPlugin::create()->install();