<?php

/* For licensing terms, see /license.txt */

/** @var \NoSearchIndex $plugin */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\FileHelper;

$plugin = NoSearchIndex::create();
$plugin_info = $plugin->get_info();

$isPlatformAdmin = api_is_platform_admin();
$editFile = false;

$file = api_get_path(SYS_PATH).'robots.txt';
$originalFile = api_get_path(SYS_PATH).'robots.dist.txt';
//$extraContentFile = api_get_home_path().'header_extra_content.txt';

if ($isPlatformAdmin) {
    /** @var FormValidator $form */
    $form = $plugin_info['settings_form'];

    if ($form && $form->validate()) {
        if (is_writable(api_get_path(SYS_PATH))) {
            if (!Container::$container->get(FileHelper::class)->exists($originalFile)) {
                copy($file, $originalFile);
            }
        } else {
            Display::addFlash(
                Display::return_message(
                    sprintf(
                        $plugin->get_lang('CheckDirectoryPermissionsInX'),
                        api_get_path(SYS_PATH)
                    ),
                    'warning'
                )
            );
        }

        if (!Container::$container->get(FileHelper::class)->exists($extraContentFile)) {
            Container::$container->get(FileHelper::class)->write($extraContentFile, '');
        }

        $values = $form->getSubmitValues();

        $continue = false;
        if (Container::$container->get(FileHelper::class)->exists($file) && is_readable($file) && is_writable($file) &&
            Container::$container->get(FileHelper::class)->exists($originalFile) && is_readable($originalFile) && is_writable($originalFile) &&
            Container::$container->get(FileHelper::class)->exists($extraContentFile) && is_readable($extraContentFile) && is_writable($extraContentFile)
        ) {
            $continue = true;
        }

        if ($continue) {
            $contents = Container::$container->get(FileHelper::class)->read($originalFile);
            $noFollow = '<meta name="robots" content="noindex" />';
            if (isset($values['tool_enable']) && 'true' == $values['tool_enable']) {
                $result = Container::$container->get(FileHelper::class)->write($file, $contents."\nDisallow: /\n");
                $value = '';
                if (Container::$container->get(FileHelper::class)->exists($extraContentFile)) {
                    $backup = Container::$container->get(FileHelper::class)->read($extraContentFile);
                    Container::$container->get(FileHelper::class)->write($extraContentFile, $backup.$noFollow);
                } else {
                    $value = Container::$container->get(FileHelper::class)->write($extraContentFile, $noFollow);
                }
            } else {
                Container::$container->get(FileHelper::class)->write($file, $contents);

                if (Container::$container->get(FileHelper::class)->exists($extraContentFile)) {
                    $backup = Container::$container->get(FileHelper::class)->read($extraContentFile);
                    $backup = str_replace($noFollow, '', $backup);
                    Container::$container->get(FileHelper::class)->write($extraContentFile, $backup);
                }
            }
        } else {
            api_delete_settings_params(
                [
                    'category = ? AND access_url = ? AND subkey = ? AND type = ? and variable = ?' => [
                        'Plugins',
                        api_get_current_access_url_id(),
                        'nosearchindex',
                        'setting',
                        'nosearchindex_tool_enable',
                    ],
                ]
            );
            $form->setElementError('tool_enable', $plugin->get_lang('CheckTheWritingPermissionsOfRobotsFile'));
        }
    }
    $plugin_info['settings_form'] = $form;
}
