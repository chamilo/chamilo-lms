<?php
/* For licensing terms, see /license.txt */

/**
 * StudentFollowUpPlugin.
 *
 * @package chamilo.plugin
 */
$plugin = StudentFollowUpPlugin::create();
$plugin_info = $plugin->get_info();

$form = new FormValidator('htaccess');
//$form->addText('text', 'text', ['rows' => '15']);
$form->addButtonSave(get_lang('Save'));

if ($form->validate()) {
}

$plugin_info['templates'] = ['view/post.html.twig', 'view/posts.html.twig'];
$plugin_info['settings_form'] = $form;
