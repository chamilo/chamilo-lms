<?php

/**
 * 
 * @see http://www.google.com/uds/solutions/dynamicfeed/index.html
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */

global $language_files;
$language_files[] = 'plugin_rss';

$plugin_info['title'] = 'Rss';
$plugin_info['comment'] = 'Rss';
$plugin_info['version'] = '1.1';
$plugin_info['author'] = 'Laurent Opprecht';


$rss = '';
$title = '';
$plugin_settings = api_get_settings_params(array("subkey = ? AND category = ? AND type = ? " => array('rss', 'Plugins', 'setting')));
$plugin_settings = $plugin_settings ? $plugin_settings : array();
foreach ($plugin_settings as $setting)
{
    if ($setting['variable'] == 'rss_rss')
    {
        $rss = $setting['selected_value'];
    }
    else if ($setting['variable'] == 'rss_title')
    {
        $title = $setting['selected_value'];
    }
}

$form = new FormValidator('rss');
$form->addElement('text', 'title', get_lang('title'));
$form->addElement('text', 'rss', 'Rss');
$form->addElement('style_submit_button', 'submit_button', get_lang('Save'));
$form->setDefaults(array('rss' => $rss, 'title' => $title));

$plugin_info['settings_form'] = $form;
$plugin_info['rss'] = $rss;
$plugin_info['rss_title'] = $title;
