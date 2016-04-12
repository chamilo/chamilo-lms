<?php
/**
 * This script is a configuration file for the vchamilo plugin. You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins)
 * @package chamilo.plugin
 * @author Julio Montoya <gugli100@gmail.com>
 */

require_once(api_get_path(LIBRARY_PATH).'plugin.class.php');
require_once(api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/vchamilo_plugin.class.php');
require_once(api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib.php');

global $_configuration;

/**
 * Plugin details (must be present)
 */

/* Plugin config */

//the plugin title
$plugin_info['title']       = 'Chamilo Virtualization';
//the comments that go with the plugin
$plugin_info['comment']     = "Holds chamilo virtualisation tools";
//the plugin version
$plugin_info['version']     = '1.0';
//the plugin author
$plugin_info['author']      = 'Valery Fremaux';


/* Plugin optional settings */

/*
 * This form will be showed in the plugin settings once the plugin was installed
 * in the plugin/hello_world/index.php you can have access to the value: $plugin_info['settings']['hello_world_show_type']
*/

$form = new FormValidator('vchamilo_form');

$plugininstance = VChamiloPlugin::create();

$form_settings = array(
    'enable_virtualisation' => vchamilo_get_config('vchamilo', 'enable_virtualisation', true),
    'httpproxyhost' => vchamilo_get_config('vchamilo', 'httpproxyhost', true),
    'httpproxyport' => vchamilo_get_config('vchamilo', 'httpproxyport', true),
    'httpproxybypass' => vchamilo_get_config('vchamilo', 'httpproxybypass', true),
    'httpproxyuser' => vchamilo_get_config('vchamilo', 'httpproxyuser', true),
    'httpproxypassword' => vchamilo_get_config('vchamilo', 'httpproxypassword', true),
    'cmd_mysql' => vchamilo_get_config('vchamilo', 'cmd_mysql', true),
    'cmd_mysqldump' => vchamilo_get_config('vchamilo', 'cmd_mysqldump', true),
    'course_real_root' => vchamilo_get_config('vchamilo', 'course_real_root', true),
    'archive_real_root' => vchamilo_get_config('vchamilo', 'archive_real_root', true),
    'home_real_root' => vchamilo_get_config('vchamilo', 'home_real_root', true),
);

$form->setDefaults($form_settings);

$wwwroot = $_configuration['root_web'];

//A simple select
$options = array(0 => $plugininstance->get_lang('no'), 1 => $plugininstance->get_lang('yes'));
$form->addElement('static', 'enable_vchamilo_manager', '<a href="'.api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php">'.$plugininstance->get_lang('manage_instances').'</a>');
$form->addElement('static', 'sync_vchamilo_settings', '<a href="'.api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/syncparams.php">'.$plugininstance->get_lang('sync_settings').'</a>');
$form->addElement('header', $plugininstance->get_lang('enabling'));
$form->addElement('select', 'enable_virtualisation', $plugininstance->get_lang('enable_virtualisation'), $options);
$form->addElement('text', 'course_real_root', $plugininstance->get_lang('courserealroot'));
$form->addElement('text', 'archive_real_root', $plugininstance->get_lang('archiverealroot'));
$form->addElement('text', 'home_real_root', $plugininstance->get_lang('homerealroot'));

$form->addElement('header', $plugininstance->get_lang('proxysettings'));
$form->addElement('text', 'httpproxyhost', $plugininstance->get_lang('httpproxyhost'));
$form->addElement('text', 'httpproxyport', $plugininstance->get_lang('httpproxyport'));
$form->addElement('text', 'httpproxybypass', $plugininstance->get_lang('httpproxybypass'));
$form->addElement('text', 'httpproxyuser', $plugininstance->get_lang('httpproxyuser'));
$form->addElement('text', 'httpproxypassword', $plugininstance->get_lang('httpproxypassword'));

$form->addElement('header', $plugininstance->get_lang('mysqlcmds'));
$form->addElement('text', 'cmd_mysql', $plugininstance->get_lang('mysqlcmd'));
$form->addElement('text', 'cmd_mysqldump', $plugininstance->get_lang('mysqldumpcmd'));

$form->addButtonSave($plugininstance->get_lang('Save'));

$plugin_info['settings_form'] = $form;

//set the templates that are going to be used
$plugin_info['templates']   = array('template.tpl');
