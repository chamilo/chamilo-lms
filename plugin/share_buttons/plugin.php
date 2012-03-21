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

/* Plugin config */

//the plugin title
$plugin_info['title']       = 'Share this page';
//the comments that go with the plugin
$plugin_info['comment']     = "Show social icons to share a page using addthis.com";
//the plugin version
$plugin_info['version']     = '1.0';
//the plugin author
$plugin_info['author']      = 'Julio Montoya';


/* Plugin optional settings */ 

/* 
 * This form will be showed in the plugin settings once the plugin was installed 
 * in the plugin/hello_world/index.php you can have access to the value: $plugin_info['settings']['hello_world_show_type']
*/

$form = new FormValidator('hello_world_form');

//A simple select
$options = array('hello_world' => 'Hello World', 'hello' =>'Hello', 'hi' =>'Hi!');
$form->addElement('select', 'show_type', 'Hello world types', $options);
$form->addElement('style_submit_button', 'submit_button', get_lang('Save'));  

$plugin_info['settings_form'] = $form;