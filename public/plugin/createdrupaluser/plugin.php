<?php
/* For licensing terms, see /license.txt */
/**
 * Get the plugin info.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.createDrupalUser
 */
require_once __DIR__.'/config.php';

$plugin_info = CreateDrupalUser::create()->get_info();
