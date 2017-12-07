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
    $extraContentFile = api_get_home_path().'header_extra_content.txt';
    if (!file_exists($originalFile)) {
        copy($file, $originalFile);
    }
    if (!file_exists($extraContentFile)) {
        file_put_contents($extraContentFile, '');
    }

    $originalContent = file_get_contents($originalFile);
    /** @var FormValidator $form */
    $form = $plugin_info['settings_form'];

    if ($form && $form->validate()) {
        $values = $form->getSubmitValues();
        $continue = false;
        if (is_readable($file) && is_writable($file) &&
            file_exists($originalFile) && is_readable($originalFile) && is_writable($originalFile) &&
            file_exists($extraContentFile) && is_readable($extraContentFile) && is_writable($extraContentFile)
        ) {
            $continue = true;
        }
        $continue = false;

        if ($continue) {
            $contents = file_get_contents($originalFile);
            $noFollow = '<meta name="robots" content="noindex" />';
            if (isset($values['tool_enable']) && $values['tool_enable'] == 'true') {
                $result = file_put_contents($file, $contents."\nDisallow: /\n");
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
        } else {
            /*Display::addFlash(
                Display::return_message($plugin->get_lang('CheckTheWritingPermissionsOfRobotsFile'), 'warning')
            );*/
            api_delete_settings_params(
                [
                    'category = ? AND access_url = ? AND subkey = ? AND type = ? and variable = ?' => [
                        'Plugins',
                        api_get_current_access_url_id(),
                        'nosearchindex',
                        'setting',
                        'nosearchindex_tool_enable',
                    ]
                ]
            );
            $form->setElementError('tool_enable', $plugin->get_lang('CheckTheWritingPermissionsOfRobotsFile'));
        }
    }
    $plugin_info['settings_form'] = $form;
}

