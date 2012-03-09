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
$plugin_info['title']       = 'Hello world';
//the comments that go with the plugin
$plugin_info['comment']     = "Shows a hello world message";
//the plugin version
$plugin_info['version']     = '1.0';
//the plugin author
$plugin_info['author']      = 'Julio Montoya';


//More complex options for the plugin (optional)

$form = new FormValidator();

//A simple select
$options = array('hello_world' => 'Hello World', 'hello' =>'Hello', 'hi' =>'Hi!');
$form->addElement('select', 'show_type', 'Hello world types', $options);
$form->addElement('style_submit_button', 'submit_button', get_lang('Save'));  

$plugin_info['settings_form'] = $form;