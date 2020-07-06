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

//the plugin title
$plugin_info['title'] = 'Hide/Unhide the Login/Password default form';

//the comments that go with the plugin
$plugin_info['comment'] = "If you use another way of authentication than local, you may want to hide the Login/Password default Form to avoid users mistakes. This plugin replace the Login/Password form with a text that unhide the Login/Password form if you click on it.";
//the plugin version
$plugin_info['version'] = '1.0';
//the plugin author
$plugin_info['author'] = 'Hubert Borderiou';

//the plugin configuration
$form = new FormValidator('add_cas_button_form');
$form->addElement('text', 'label', 'Text label', '');
//get default value
$tab_default_formLogin_hide_unhide_label = api_get_setting('formLogin_hide_unhide_label');
$defaults = [];
if ($tab_default_formLogin_hide_unhide_label) {
    $defaults['label'] = $tab_default_formLogin_hide_unhide_label['formLogin_hide_unhide'];
}

$form->setDefaults($defaults);
//display form
$plugin_info['settings_form'] = $form;

//set the templates that are going to be used
$plugin_info['templates'] = ['template.tpl'];
