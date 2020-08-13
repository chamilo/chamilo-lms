<?php
/* For licensing terms, see /license.txt */
/**
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.azure_active_directory
 */
$plugin_info = AzureActiveDirectory::create()->get_info();

$plugin_info['templates'] = ['view/block.tpl'];
