<?php
/* For licensing terms, see /license.txt */
/**
 * Init the plugin
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.advancedskills
 */
require_once __DIR__.'/config.php';

$plugin_info = AdvancedSkills::create()->get_info();
