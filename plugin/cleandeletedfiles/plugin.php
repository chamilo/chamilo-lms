<?php
/* For licensing terms, see /license.txt */

/**
 * Plugin.
 *
 * @author Jose Angel Ruiz
 *
 * @package chamilo.plugin.CleanDeletedFiles
 */

/* Plugin config */
require_once __DIR__.'/config.php';
$plugin_info = CleanDeletedFilesPlugin::create()->get_info();
