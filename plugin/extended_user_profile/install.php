<?php
/* For license terms, see /license.txt */
/**
 * Install the Extended User Profile plugin
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.extended_user_profile
 */
require_once dirname(__FILE__) . '/config.php';

if (!api_is_platform_admin()) {
    die('You must have admin permissions to install plugins');
}

$plugin_info = ExtendedUserProfilePlugin::create()->install();
