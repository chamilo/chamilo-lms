<?php

/* For license terms, see /license.txt */

use OneLogin\Saml2\Settings;

require_once '../../../main/inc/global.inc.php';

/**
 *  SAML Metadata view.
 */
require_once 'settings.php';

try {
    // Now we only validate SP settings
    $settings = new Settings($settingsInfo, true);
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
