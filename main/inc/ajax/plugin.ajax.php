<?php
/* For licensing terms, see /license.txt */
use Michelf\MarkdownExtra;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

api_block_anonymous_users();

$action = $_REQUEST['a'];

switch ($action) {
    case 'md_to_html':
        $plugin = $_GET['plugin'] ?? '';
        $appPlugin = new AppPlugin();

        $pluginPaths = $appPlugin->read_plugins_from_path();

        if (!in_array($plugin, $pluginPaths)) {
            echo Display::return_message(get_lang('NotAllowed'), 'error', false);
            exit;
        }

        $pluginInfo = $appPlugin->getPluginInfo($plugin);

        $html = '';
        if (!empty($pluginInfo)) {
            $file = api_get_path(SYS_PLUGIN_PATH).$plugin.'/README.md';
            if (file_exists($file)) {
                $content = file_get_contents($file);

                $html = MarkdownExtra::defaultTransform($content);
            }
        }
        echo $html;
        break;
}
