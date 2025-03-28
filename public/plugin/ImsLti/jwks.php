<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\Platform;
use Firebase\JWT\JWT;
use phpseclib\Crypt\RSA;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = ImsLtiPlugin::create();

if ($plugin->get('enabled') !== 'true') {
    exit;
}

/** @var Platform $platform */
$platform = Database::getManager()
    ->getRepository('ChamiloPluginBundle:ImsLti\Platform')
    ->findOneBy([]);

if (!$platform) {
    exit;
}

$jwks = [];

$key = new RSA();
$key->setHash('sha256');
$key->loadKey($platform->getPrivateKey());
$key->setPublicKey(false, RSA::PUBLIC_FORMAT_PKCS8);

if ($key->publicExponent) {
    $jwks = [
        'kty' => 'RSA',
        'alg' => 'RS256',
        'use' => 'sig',
        'e' => JWT::urlsafeB64Encode($key->publicExponent->toBytes()),
        'n' => JWT::urlsafeB64Encode($key->modulus->toBytes()),
        'kid' => $platform->getKid(),
    ];
}

header('Content-Type: application/json');

echo json_encode(['keys' => [$jwks]]);
