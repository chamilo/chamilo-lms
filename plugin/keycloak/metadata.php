<?php
/* For license terms, see /license.txt */

require_once '../../main/inc/global.inc.php';

$pluginKeycloak = api_get_plugin_setting('keycloak', 'tool_enable') === 'true';

if (!$pluginKeycloak) {
    api_not_allowed(true);
}

/**
 *  SAML Metadata view.
 */
require_once 'settings.php';

try {
    // Now we only validate SP settings
    $settings = new \OneLogin\Saml2\Settings($settingsInfo, true);
    $metadata = $settings->getSPMetadata();
    $errors = $settings->validateMetadata($metadata);
    if (empty($errors)) {
        header('Content-Type: text/xml');
        echo $metadata;
    } else {
        throw new OneLogin\Saml2\Error('Invalid SP metadata: '.implode(', ', $errors), OneLogin\Saml2\Error::METADATA_SP_INVALID);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
