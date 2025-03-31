<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\LtiProvider\PlatformKey;
use Firebase\JWT\JWT;
use phpseclib\Crypt\RSA;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = LtiProviderPlugin::create();

if ('true' !== $plugin->get('enabled')) {
    exit;
}

/** @var PlatformKey $platformKey */
$platformKey = Database::getManager()
    ->getRepository('ChamiloPluginBundle:LtiProvider\PlatformKey')
    ->findOneBy([]);

if (!$platformKey) {
    exit;
}

$privateKey = $platformKey->getPrivateKey();

$jwks = [];

$key = new RSA();
$key->setHash('sha256');
$key->loadKey($platformKey->getPrivateKey());
$key->setPublicKey(false, RSA::PUBLIC_FORMAT_PKCS8);

if ($key->publicExponent) {
    $jwks = [
        'kty' => 'RSA',
        'alg' => 'RS256',
        'use' => 'sig',
        'e' => JWT::urlsafeB64Encode($key->publicExponent->toBytes()),
        'n' => JWT::urlsafeB64Encode($key->modulus->toBytes()),
        'kid' => $platformKey->getKid(),
    ];
}

header('Content-Type: application/json');

echo json_encode(['keys' => [$jwks]]);
