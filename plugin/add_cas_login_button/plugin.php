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
$plugin_info['title']      = 'Add a button to login using CAS';

//the comments that go with the plugin
$plugin_info['comment']     = "If CAS is activated, this plugin add a text and a button on the login page to login with CAS. Configure plugin to add title, comment and logo.";
//the plugin version
$plugin_info['version']     = '1.0';
//the plugin author
$plugin_info['author']      = 'Hubert Borderiou';

//the plugin configuration
$form = new FormValidator('add_cas_button_form');
$form->addElement('text', 'cas_button_label', 'CAS connexion title', '');
$form->addElement('text', 'cas_button_comment', 'CAS connexion description', '');
$form->addElement('text', 'cas_image_url', 'Logo URL if any (image, 50px height)');
$form->addElement('style_submit_button', 'submit_button', get_lang('Save'));  
//get default value for form
$tab_default_add_cas_login_button_cas_button_label = api_get_setting('add_cas_login_button_cas_button_label');
$tab_default_add_cas_login_button_cas_button_comment = api_get_setting('add_cas_login_button_cas_button_comment');
$tab_default_add_cas_login_button_cas_image_url = api_get_setting('add_cas_login_button_cas_image_url');
$defaults = array();
$defaults['cas_button_label'] = $tab_default_add_cas_login_button_cas_button_label['add_cas_login_button'];
$defaults['cas_button_comment'] = $tab_default_add_cas_login_button_cas_button_comment['add_cas_login_button'];
$defaults['cas_image_url'] = $tab_default_add_cas_login_button_cas_image_url['add_cas_login_button'];
$form->setDefaults($defaults);
//display form
$plugin_info['settings_form'] = $form;

//set the smarty templates that are going to be used
$plugin_info['templates']   = array('template.tpl');
