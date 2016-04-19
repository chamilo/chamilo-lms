<?php
/**
 * This script is a configuration file for the delivery tools plugin. You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins)
 * @package chamilo.plugin
 * @author Julio Montoya <gugli100@gmail.com>
 */

require_once __DIR__.'/lib/customfooter_plugin.class.php';

/**
 * Plugin details (must be present)
 */

/* Plugin config */

//the plugin title
$plugin_info['title'] = 'Custom Footer';
//the comments that go with the plugin
$plugin_info['comment'] = "Drives configuration parameters that plugs custom footer notes";
//the plugin version
$plugin_info['version'] = '1.0';
//the plugin author
$plugin_info['author'] = 'Valery Fremaux, Julio Montoya';


/* Plugin optional settings */

/*
 * This form will be showed in the plugin settings once the plugin was installed
 * in the plugin/hello_world/index.php you can have access to the value: $plugin_info['settings']['hello_world_show_type']
*/

$form = new FormValidator('customfooter_form');

$plugininstance = CustomFooterPlugin::create();

$config = api_get_settings_params(array('subkey = ? ' => 'customfooter', ' AND category = ? ' => 'Plugins'));
$form_settings = [];

foreach ($config as $fooid => $configrecord) {
    $canonic = preg_replace('/^customfooter_/', '', $configrecord['variable']);
    if (in_array($canonic, array('footer_left', 'footer_right'))){
        $form_settings[$canonic] = $configrecord['selected_value'];
    }
}

// A simple select.
$form->addElement('text', 'footer_left', $plugininstance->get_lang('footerleft'));
$form->addElement('text', 'footer_right', $plugininstance->get_lang('footerright'));

$form->addButtonSave($plugininstance->get_lang('Save'));

$form->setDefaults($form_settings);

$plugin_info['settings_form'] = $form;