<?php
/* For licensing terms, see /license.txt */
/**
 * This script is included by main/admin/settings.lib.php and generally 
 * includes things to execute in the main database (settings_current table)
 * @package chamilo.plugin.olpc_peru_filter
 */
/**
 * Initialization
 */

require_once 'config.php';
OLPC_Peru_FilterPlugin::create()->install();