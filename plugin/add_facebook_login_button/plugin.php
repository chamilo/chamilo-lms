<?php
/**
 * This script is a configuration file for the date plugin. You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins)
 * @package chamilo.plugin
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Plugin details (must be present)
 */



//the plugin title
$plugin_info['title']      = 'Add a button to login using FACEBOOK account';

//the comments that go with the plugin
$plugin_info['comment']     = "If Facebook authntification is activated, this plugin add a button Facebook Connexion on the login page. Configure plugin to add title, comment and logo. Should be place in login_top region";
//the plugin version
$plugin_info['version']     = '1.0';
//the plugin author
$plugin_info['author']      = 'Hubert Borderiou';
//the plugin configuration
$form = new FormValidator('add_facebook_button_form');
$form->addElement('text', 'facebook_button_url', 'Facebook connexion image URL', '');
$form->addElement('style_submit_button', 'submit_button', get_lang('Save'));  
//get default value for form
$tab_default_add_facebook_login_button_facebook_button_url = api_get_setting('add_facebook_login_button_facebook_button_url');
$defaults['facebook_button_url'] = $tab_default_add_facebook_login_button_facebook_button_url['add_facebook_login_button'];
$form->setDefaults($defaults);
//display form
$plugin_info['settings_form'] = $form;

//set the smarty templates that are going to be used
$plugin_info['templates']   = array('template.tpl');
