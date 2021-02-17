<?php

/* For licensing terms, see /license.txt */

/**
 * Updates the Chamilo files from version 1.10.0 to version 1.11.0
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
 */
error_log('Starting '.basename(__FILE__));

global $debug;

if (defined('SYSTEM_INSTALLATION')) {
    // Changes for 2.0.0
    $pluginPath = api_get_path(SYS_PLUGIN_PATH);

    // The ims_lti plugin has been integrated to core in 2.0
    $ltiPluginPath = $pluginPath.'ims_lti';

    if (is_dir($ltiPluginPath)) {
        @rrmdir($ltiPluginPath);
    }

    // Copy configuration.php from app/config/configuration.php to config/configuration.php
    $old = api_get_path(SYMFONY_SYS_PATH).'app/config/configuration.php';
    $new = api_get_path(SYMFONY_SYS_PATH).'config/configuration.php';
    copy($old, $new);

    error_log('Finish script '.basename(__FILE__));
} else {
    echo 'You are not allowed here !'.__FILE__;
}
