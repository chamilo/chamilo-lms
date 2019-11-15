<?php
/**
 * Plugin details (must be present).
 */

//the plugin title
$plugin_info['title'] = 'Add a button to login using Okta account';

//the comments that go with the plugin
$plugin_info['comment'] = 'If Okta authntification is activated, this plugin add a button ' .
    'Okta Connexion on the login page. Configure plugin to add title, comment and logo. ' .
    'Should be place in login_top region';
//the plugin version
$plugin_info['version'] = '0.1';
//the plugin author
$plugin_info['author'] = 'Francis Gonzales';

//the plugin configuration
$form = new FormValidator('add_okta_button_form');
$form->addElement(
    'text',
    'okta_button_url',
    'Okta connexion image URL',
    ''
);
$form->addButtonSave(get_lang('Save'), 'submit_button');
//get default value for form
$tab_default_add_okta_login_button_okta_button_url = api_get_setting(
    'add_okta_login_button_okta_button_url'
);
$defaults['okta_button_url'] = $tab_default_add_okta_login_button_okta_button_url['add_okta_login_button'];
$form->setDefaults($defaults);
//display form
$plugin_info['settings_form'] = $form;

// Set the templates that are going to be used
$plugin_info['templates'] = ['template.tpl'];
