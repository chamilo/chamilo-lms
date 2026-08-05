<?php
/* For licensing terms, see /license.txt */

/**
 * Controlled file proxy for CustomCertificate private plugin storage.
 */

$cidReset = true;

require_once __DIR__.'/../config.php';

api_block_anonymous_users();

$plugin = CustomCertificatePlugin::create();

if (!$plugin->isEnabled(true)) {
    api_not_allowed();
}

$path = $_GET['path'] ?? '';

if ('' === $path) {
    http_response_code(404);
    exit;
}

CustomCertificatePlugin::outputStoredFile($path);
