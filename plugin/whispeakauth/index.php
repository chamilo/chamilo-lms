<?php
/* For licensing terms, see /license.txt */

$plugin = WhispeakAuthPlugin::create();

if ($plugin->toolIsEnabled()) {
    echo Display::toolbarButton(
        $plugin->get_lang('SpeechAuthentication'),
        api_get_path(WEB_PLUGIN_PATH).'whispeakauth/authentify.php',
        'sign-in',
        'info',
        ['class' => 'btn-block']
    );
}
