<?php
/* For license terms, see /license.txt */

$plugin_info = [
    'title'   => 'LtiProvider',
    'version' => '2.9',
];

try {
    require_once __DIR__.'/LtiProviderPlugin.php';

    if (class_exists('LtiProviderPlugin', false) && method_exists('LtiProviderPlugin', 'create')) {
        $plugin_info = LtiProviderPlugin::create()->get_info();
    } else {
        $plugin_info['broken'] = true;
        $plugin_info['error']  = 'class_or_factory_missing';
    }
} catch (\Throwable $e) {
    error_log('[LtiProvider plugin.php] '.$e->getMessage());
    $plugin_info['broken'] = true;
    $plugin_info['error']  = 'init_failed';
}
