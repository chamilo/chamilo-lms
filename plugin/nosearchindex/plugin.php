<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin
 */

/** @var \NoSearchIndex $plugin */
$plugin = NoSearchIndex::create();
$plugin_info = $plugin->get_info();

$isPlatformAdmin = api_is_platform_admin();
$editFile = false;

$file = api_get_path(SYS_PATH).'robots.txt';

if ($isPlatformAdmin) {
    $originalFile = api_get_path(SYS_PATH).'robots.dist.txt';
    if (!file_exists($originalFile)) {
        copy($file, $originalFile);
    }

    $originalContent = file_get_contents($originalFile);

    $form = $plugin_info['settings_form'];

    if ($form && $form->validate()) {
        $values = $form->getSubmitValues();
        $contents = file_get_contents($originalFile);
        $extraContentFile = api_get_home_path().'header_extra_content.txt';
        $noFollow = '<meta name="robots" content="noindex" />';

        if (isset($values['tool_enable']) && $values['tool_enable'] == 'true') {
            file_put_contents($file, $contents."\nDisallow: /\n");
            $value = '';
            if (file_exists($extraContentFile)) {
                $backup = file_get_contents($extraContentFile);
                file_put_contents($extraContentFile, $backup.$noFollow);
            } else {
                $value = file_put_contents($extraContentFile, $noFollow);
            }
        } else {
            file_put_contents($file, $contents);

            if (file_exists($extraContentFile)) {
                $backup = file_get_contents($extraContentFile);
                $backup = str_replace($noFollow, '', $backup);
                file_put_contents($extraContentFile, $backup);
            }
        }
    }
    $plugin_info['settings_form'] = $form;
}

