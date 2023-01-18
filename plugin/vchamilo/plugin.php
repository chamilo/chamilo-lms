<?php
/**
 * This script is a configuration file for the vchamilo plugin.
 * You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins
 * (Chamilo configuration settings->Plugins).
 *
 * @package chamilo.plugin
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
global $_configuration;

/**
 * Plugin details (must be present).
 */

/* Plugin config */

//the plugin title
$plugin_info['title'] = 'Chamilo Virtualization';
//the comments that go with the plugin
$plugin_info['comment'] = "Allows the virtualization of Chamilo. Use your main Chamilo installation as a hub, then create instances based on the same code but with different databases, and generate snapshots, and manage all instances like VM images.";
//the plugin version
$plugin_info['version'] = '1.2';
//the plugin author
$plugin_info['author'] = 'Valery Fremaux, Julio Montoya';

/* Plugin optional settings */

/*
 * This form will be showed in the plugin settings once the plugin was installed
 * in the plugin/hello_world/index.php you can have access to the value: $plugin_info['settings']['hello_world_show_type']
*/

$form = new FormValidator('vchamilo_form');

$plugin = VChamiloPlugin::create();

$form_settings = [
    'enable_virtualisation' => Virtual::getConfig('vchamilo', 'enable_virtualisation', true),
    'httpproxyhost' => Virtual::getConfig('vchamilo', 'httpproxyhost', true),
    'httpproxyport' => Virtual::getConfig('vchamilo', 'httpproxyport', true),
    'httpproxybypass' => Virtual::getConfig('vchamilo', 'httpproxybypass', true),
    'httpproxyuser' => Virtual::getConfig('vchamilo', 'httpproxyuser', true),
    'httpproxypassword' => Virtual::getConfig('vchamilo', 'httpproxypassword', true),
    'cmd_mysql' => Virtual::getConfig('vchamilo', 'cmd_mysql', true),
    'cmd_mysqldump' => Virtual::getConfig('vchamilo', 'cmd_mysqldump', true),
    'course_real_root' => Virtual::getConfig('vchamilo', 'course_real_root', true),
    'archive_real_root' => Virtual::getConfig('vchamilo', 'archive_real_root', true),
    'home_real_root' => Virtual::getConfig('vchamilo', 'home_real_root', true),
    'upload_real_root' => Virtual::getConfig('vchamilo', 'upload_real_root', true),
];

$form->setDefaults($form_settings);

$wwwroot = $_configuration['root_web'];

//A simple select
$options = [0 => $plugin->get_lang('no'), 1 => $plugin->get_lang('yes')];
$form->addLabel(
    '',
    '<a class="btn btn-primary" href="'.api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php">'.
    $plugin->get_lang('manage_instances').'</a>'
);
$form->addElement('header', $plugin->get_lang('enabling'));
$form->addElement('select', 'enable_virtualisation', $plugin->get_lang('enable_virtualisation'), $options);
$form->addElement(
    'text',
    'course_real_root',
    [$plugin->get_lang('courserealroot'), 'Example: '.api_get_path(SYS_PATH).'var/courses/']
);
$form->addElement(
    'text',
    'archive_real_root',
    [$plugin->get_lang('archiverealroot'), 'Example: '.api_get_path(SYS_PATH).'var/archive/']
);
$form->addElement(
    'text',
    'home_real_root',
    [$plugin->get_lang('homerealroot'), 'Example: '.api_get_path(SYS_PATH).'var/home/']
);

$form->addElement(
    'text',
    'upload_real_root',
    [$plugin->get_lang('UploadRealRoot'), 'Example: '.api_get_path(SYS_PATH).'var/upload/']
);

$form->addElement('header', $plugin->get_lang('mysqlcmds'));
$form->addElement('text', 'cmd_mysql', [$plugin->get_lang('mysqlcmd'), 'Example: /usr/bin/mysql']);
$form->addElement('text', 'cmd_mysqldump', [$plugin->get_lang('mysqldumpcmd'), 'Example: /usr/bin/mysqldump']);
$form->addElement('header', $plugin->get_lang('proxysettings'));
$form->addElement('text', 'httpproxyhost', $plugin->get_lang('httpproxyhost'));
$form->addElement('text', 'httpproxyport', $plugin->get_lang('httpproxyport'));
$form->addElement('text', 'httpproxybypass', $plugin->get_lang('httpproxybypass'));
$form->addElement('text', 'httpproxyuser', $plugin->get_lang('httpproxyuser'));
$form->addElement('text', 'httpproxypassword', $plugin->get_lang('httpproxypassword'));
$form->addButtonSave($plugin->get_lang('Save'));

$plugin_info['settings_form'] = $form;

// Set the templates that are going to be used
$plugin_info['templates'] = ['template.tpl'];

$plugin_info['plugin_class'] = get_class($plugin);
