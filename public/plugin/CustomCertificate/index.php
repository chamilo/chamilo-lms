<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';

$plugin = CustomCertificatePlugin::create();

if (!$plugin->isEnabled(true)) {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'CustomCertificate/start.php');
exit;
