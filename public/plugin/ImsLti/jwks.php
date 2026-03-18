<?php
/* For licensing terms, see /license.txt */

use Chamilo\LtiBundle\Entity\Platform;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

ImsLtiPlugin::create();

/** @var Platform|null $platform */
$platform = Database::getManager()
    ->getRepository(Platform::class)
    ->findOneBy([]);

if (!$platform) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['keys' => []], JSON_UNESCAPED_SLASHES);
    exit;
}

$publicKeyPem = '';

if (property_exists($platform, 'publicKey') && is_string($platform->publicKey) && '' !== trim($platform->publicKey)) {
    $publicKeyPem = trim($platform->publicKey);
} elseif (method_exists($platform, 'getPublicKey')) {
    $publicKeyPem = trim((string) $platform->getPublicKey());
}

if ('' === $publicKeyPem && method_exists($platform, 'getPrivateKey')) {
    $privateKeyPem = trim((string) $platform->getPrivateKey());

    if ('' !== $privateKeyPem) {
        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if (false !== $privateKey) {
            $privateKeyDetails = openssl_pkey_get_details($privateKey);

            if (is_array($privateKeyDetails) && !empty($privateKeyDetails['key'])) {
                $publicKeyPem = trim((string) $privateKeyDetails['key']);
            }
        }
    }
}

if ('' === $publicKeyPem) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['keys' => []], JSON_UNESCAPED_SLASHES);
    exit;
}

$publicKey = openssl_pkey_get_public($publicKeyPem);

if (false === $publicKey) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['keys' => []], JSON_UNESCAPED_SLASHES);
    exit;
}

$keyDetails = openssl_pkey_get_details($publicKey);

if (
    !is_array($keyDetails) ||
    ($keyDetails['type'] ?? null) !== OPENSSL_KEYTYPE_RSA ||
    empty($keyDetails['rsa']) ||
    !is_array($keyDetails['rsa']) ||
    empty($keyDetails['rsa']['n']) ||
    empty($keyDetails['rsa']['e'])
) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['keys' => []], JSON_UNESCAPED_SLASHES);
    exit;
}

$base64UrlEncode = static function (string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
};

$jwks = [
    'keys' => [
        [
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => (string) $platform->getKid(),
            'n' => $base64UrlEncode($keyDetails['rsa']['n']),
            'e' => $base64UrlEncode($keyDetails['rsa']['e']),
        ],
    ],
];

header('Content-Type: application/json');
echo json_encode($jwks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
