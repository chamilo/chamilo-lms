<?php
/**
 * This script is a configuration file for the add_this plugin.
 * These settings will be used in the administration interface for plugins
 * (Chamilo configuration settings->Plugins)
 * @package chamilo.plugin chamilo_boost
 * @author Damien Renou <renou.damien@live.fr>
 */
 
/* Plugin config */

require_once(__DIR__.'/chamilo_boost.php');
$plugin_info = chamilo_boost::create()->get_info();

// The plugin title.
$plugin_info['title'] = 'chamilo_boost';
// The comments that go with the plugin.
$plugin_info['comment'] = "Show a chamilo_boost (zone : pre_footer)";
// The plugin version.
$plugin_info['version'] = '1.1';
// The plugin author.
$plugin_info['author'] = 'Damien Renou';

require_once 'inc/functions.php';

// The plugin configuration.
$form = new FormValidator('form');

$defaults = array();

$aid = api_get_current_access_url_id();

$defaults['optionTitle'] = api_get_plugin_setting_access_urlB('chamilo_boost','optionTitle',$aid);

$defaults['optionMode'] = api_get_plugin_setting_access_urlB('chamilo_boost','optionMode',$aid);

$defaults['dossierinterface'] = api_get_plugin_setting_access_urlB('chamilo_boost','dossierinterface',$aid);

$defaults['urlinterface'] = api_get_plugin_setting_access_urlB('chamilo_boost','urlinterface',$aid);

//Titre Options de l'interface
$form->addElement('header',"Options de l'interface");
$options = array(
    'tile' => 'tile'
);

$stringTitle = $form->addElement('text', 'optionTitle'.$aid, 'Title :');
if($defaults['optionTitle']==''){
	$defaults['optionTitle'] = 'Title Dashboard';
}
$stringTitle->setValue($defaults['optionTitle']);

$autocomplete = $form->addElement('text', 'dossierinterface'.$aid, 'Dossier du style :');
if($defaults['dossierinterface']==''){
	$defaults['dossierinterface'] = 'localhost';
}
$autocomplete->setValue($defaults['dossierinterface']);

$autocomplete2 = $form->addElement('text', 'urlinterface'.$aid, 'Url du site :');
$autocomplete2->setValue($defaults['urlinterface']);

//Sauvegarde
//$form->addElement('button', 'submit_button', get_lang('Save'));
$form->addButtonSave(get_lang('Save'));

// Get default value for form

$plugin_info['settings_form'] = $form;

//set the templates that are going to be used
$plugin_info['templates'] = array('view/template.tpl');


