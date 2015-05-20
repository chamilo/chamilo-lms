<?php
/* For license terms, see /license.txt */
/**
 * Get the plugin info
 * @package chamilo.plugin.current_sessions_block
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__ . '/../../main/inc/global.inc.php';

$plugin_info = CurrentSessionsBlockPlugin::create()->get_info();

$plugin_info['templates'] = ['template/block.tpl'];
