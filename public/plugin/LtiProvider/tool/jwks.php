<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\LtiProvider\Entity\PlatformKey;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../LtiProviderPlugin.php';

header('Content-Type: application/json; charset=utf-8');

$pluginEntity = Container::getPluginRepository()->findOneByTitle('LtiProvider');
$currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
$pluginConfiguration = $pluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isPluginEnabled = $pluginEntity
    && $pluginEntity->isInstalled()
    && $pluginConfiguration
    && $pluginConfiguration->isActive();

if (!$isPluginEnabled) {
    echo json_encode([
        'keys' => [],
        'debug' => 'plugin_disabled',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$em = Container::getEntityManager();

/** @var PlatformKey|null $platformKey */
$platformKey = $em
    ->getRepository(PlatformKey::class)
    ->findOneBy([]);

if (!$platformKey) {
    echo json_encode([
        'keys' => [],
        'debug' => 'platform_key_not_found',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$privateKeyPem = trim((string) $platformKey->getPrivateKey());

if ('' === $privateKeyPem) {
    echo json_encode([
        'keys' => [],
        'debug' => 'private_key_empty',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$opensslKey = openssl_pkey_get_private($privateKeyPem);

if (false === $opensslKey) {
    $errors = [];
    while ($msg = openssl_error_string()) {
        $errors[] = $msg;
    }

    echo json_encode([
        'keys' => [],
        'debug' => 'openssl_pkey_get_private_failed',
        'errors' => $errors,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$keyDetails = openssl_pkey_get_details($opensslKey);

if (false === $keyDetails) {
    $errors = [];
    while ($msg = openssl_error_string()) {
        $errors[] = $msg;
    }

    echo json_encode([
        'keys' => [],
        'debug' => 'openssl_pkey_get_details_failed',
        'errors' => $errors,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$keyType = $keyDetails['type'] ?? null;

if (null === $keyType || OPENSSL_KEYTYPE_RSA !== (int) $keyType) {
    echo json_encode([
        'keys' => [],
        'debug' => 'key_is_not_rsa',
        'type' => $keyType,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if (
    empty($keyDetails['rsa']) ||
    empty($keyDetails['rsa']['n']) ||
    empty($keyDetails['rsa']['e'])
) {
    echo json_encode([
        'keys' => [],
        'debug' => 'rsa_components_missing',
        'rsa_keys' => !empty($keyDetails['rsa']) ? array_keys($keyDetails['rsa']) : [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$n = rtrim(strtr(base64_encode($keyDetails['rsa']['n']), '+/', '-_'), '=');
$e = rtrim(strtr(base64_encode($keyDetails['rsa']['e']), '+/', '-_'), '=');

echo json_encode([
    'keys' => [[
        'kty' => 'RSA',
        'alg' => 'RS256',
        'use' => 'sig',
        'kid' => $platformKey->getKid(),
        'n' => $n,
        'e' => $e,
    ]],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
