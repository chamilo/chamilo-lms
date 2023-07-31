<?php
/**
 * @package chamilo.plugin.add_facebook_login_button
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Plugin details.
 */

//the plugin title
$plugin_info['title'] = 'Add a button to login using a FACEBOOK account';

//the comments that go with the plugin
$plugin_info['comment'] = "If Facebook authentication is enabled, this plugin adds a button Facebook Connexion on the login page. Configure the plugin to add a title, a comment and a logo. Should be placed in login_top region";
//the plugin version
$plugin_info['version'] = '1.0';
//the plugin author
$plugin_info['author'] = 'Konrad Banasiak, Hubert Borderiou';
//the plugin configuration
$form = new FormValidator('add_facebook_button_form');
$form->addElement(
    'text',
    'facebook_button_url',
    'Facebook connexion image URL',
    ''
);
$form->addButtonSave(get_lang('Save'), 'submit_button');
//get default value for form
$tab_default_add_facebook_login_button_facebook_button_url = api_get_setting(
    'add_facebook_login_button_facebook_button_url'
);

$defaults = [];

if ($tab_default_add_facebook_login_button_facebook_button_url) {
    $defaults['facebook_button_url'] = $tab_default_add_facebook_login_button_facebook_button_url['add_facebook_login_button'];
}

$form->setDefaults($defaults);
//display form
$plugin_info['settings_form'] = $form;

// Set the templates that are going to be used
$plugin_info['templates'] = ['template.tpl'];
