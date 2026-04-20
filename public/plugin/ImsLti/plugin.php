<?php
/* For license terms, see /license.txt */

$plugin_info = [
    'title' => 'ImsLti',
    'comment' => 'IMS/LTI client plugin',
    'version' => '1.9.0',
    'author' => 'Angel Fernando Quiroz Campos',
    'plugin_class' => 'ImsLtiPlugin',
    'is_admin_plugin' => true,
];

try {
    require_once __DIR__.'/ImsLtiPlugin.php';

    if (class_exists('ImsLtiPlugin', false) && method_exists('ImsLtiPlugin', 'create')) {
        $plugin_info = array_merge($plugin_info, ImsLtiPlugin::create()->get_info());
        $plugin_info['is_admin_plugin'] = true;
    }
} catch (\Throwable $e) {
    error_log('[ImsLti plugin.php] '.$e->getMessage());
}
