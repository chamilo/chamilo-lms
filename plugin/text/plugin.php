<?php
/* See license terms in /license.txt */

/**
 * This script is a configuration file for the date plugin.
 * You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins)
 * @package chamilo.plugin
 * @author Julio Montoya <gugli100@gmail.com>
 */

/* Plugin config */

//the plugin title
$plugin_info['title'] = 'Text';
//the comments that go with the plugin
$plugin_info['comment'] = "Displays a text message";
//the plugin version
$plugin_info['version'] = '1.0';
//the plugin author
$plugin_info['author'] = 'Julio Montoya';

/* Plugin optional settings */

$form = new FormValidator('text_form');
$form->addTextarea('content', get_lang('Content'));
$form->addButtonSave(get_lang('Save'), 'submit_button');

$content = '';
$setting = api_get_setting('text_content');

if (!empty($setting) && is_array($setting)) {
    if (isset($setting['text'])) {
        $content = $setting['text'];
    }
}

$form->setDefaults(array('content' => $content));

$plugin_info['settings_form'] = $form;
