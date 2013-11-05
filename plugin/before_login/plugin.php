<?php
/**
 * This script is a configuration file for the add_this plugin.
 * These settings will be used in the administration interface for plugins
 * (Chamilo configuration settings->Plugins)
 * @package chamilo.plugin
 * @author Julio Montoya <gugli100@gmail.com>
 */

/* Plugin config */

// The plugin title.
$plugin_info['title']       = 'Show HTML before login';
// The comments that go with the plugin.
$plugin_info['comment']     = "Show a content before loading the login page.";
// The plugin version.
$plugin_info['version']     = '1.0';
// The plugin author.
$plugin_info['author']      = 'Julio Montoya';

// The plugin configuration.
$form = new FormValidator('form');

$form->addElement('header', 'Option 1');
$form->addElement('textarea', 'option1', 'Description');
$form->addElement('text', 'option1_url', 'Redirect to');

$form->addElement('header', 'Option 2');
$form->addElement('textarea', 'option2', 'Description');
$form->addElement('text', 'option2_url', 'Redirect to');
$form->addElement('button', 'submit_button', get_lang('Save'));

// Get default value for form

$defaults = array();
$defaults['option1'] = api_get_plugin_setting('before_login', 'option1');
$defaults['option2'] = api_get_plugin_setting('before_login', 'option2');

$defaults['option1_url'] = api_get_plugin_setting('before_login', 'option1_url');
$defaults['option2_url'] = api_get_plugin_setting('before_login', 'option2_url');

$plugin_info['templates']   = array('template.tpl');

$form->setDefaults($defaults);

// Display form
$plugin_info['settings_form'] = $form;
