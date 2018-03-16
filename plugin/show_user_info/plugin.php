<?php
/**
 * This script is a configuration file for the date plugin. You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins).
 *
 * @package chamilo.plugin
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Plugin details (must be present).
 */

// The plugin title
$plugin_info['title'] = 'Show user information';
// The comments that go with the plugin
$plugin_info['comment'] = "Shows a welcome message, (this is an example to uses the template system: Twig)";
// The plugin version
$plugin_info['version'] = '1.0';
// The plugin author
$plugin_info['author'] = 'Julio Montoya';
// Set the templates that are going to be used
$plugin_info['templates'] = ['template.tpl'];
