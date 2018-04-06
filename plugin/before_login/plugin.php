<?php
/**
 * This script is a configuration file for the add_this plugin.
 * These settings will be used in the administration interface for plugins
 * (Chamilo configuration settings->Plugins).
 *
 * @package chamilo.plugin
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */

/* Plugin config */

// The plugin title.
$plugin_info['title'] = 'Show HTML before login';
// The comments that go with the plugin.
$plugin_info['comment'] = "Show a content before loading the login page.";
// The plugin version.
$plugin_info['version'] = '1.0';
// The plugin author.
$plugin_info['author'] = 'Julio Montoya';

// The plugin configuration.
$form = new FormValidator('form');
$form->addElement('select', 'language', get_lang('Language'), api_get_languages_to_array());

$form->addElement('header', 'Option 1');
$form->addElement('textarea', 'option1', get_lang('Description'), ['rows' => 10, 'class' => 'span6']);
$form->addElement('text', 'option1_url', get_lang('RedirectTo'));

$form->addElement('header', 'Option 2');
$form->addElement('textarea', 'option2', get_lang('Description'), ['rows' => 10, 'class' => 'span6']);
$form->addElement('text', 'option2_url', get_lang('RedirectTo'));
$form->addElement('button', 'submit_button', get_lang('Save'));

// Get default value for form

$defaults = [];
$defaults['language'] = api_get_plugin_setting('before_login', 'language');
$defaults['option1'] = api_get_plugin_setting('before_login', 'option1');
$defaults['option2'] = api_get_plugin_setting('before_login', 'option2');

$defaults['option1_url'] = api_get_plugin_setting('before_login', 'option1_url');
$defaults['option2_url'] = api_get_plugin_setting('before_login', 'option2_url');

$plugin_info['templates'] = ['template.tpl'];
if (file_exists(__DIR__.'/custom.template.tpl')) {
    $plugin_info['templates'] = ['custom.template.tpl'];
}
$form->setDefaults($defaults);

// Display form
$plugin_info['settings_form'] = $form;
