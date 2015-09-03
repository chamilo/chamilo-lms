<?php
/* For license terms, see /license.txt */
/**
 * Get the plugin info
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.extended_user_profile
 */
require_once __DIR__ . '/config.php';

$plugin_info = ExtendedUserProfilePlugin::create()->get_info();
