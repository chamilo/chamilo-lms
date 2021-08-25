<?php
namespace Packback\Lti1p3;

use phpseclib\Crypt\RSA;
use \Firebase\JWT\JWT;

use Packback\Lti1p3\Interfaces\Database;
use Packback\Lti1p3\Interfaces\LtiRegistrationInterface;

class JwksEndpoint
{

    private $keys;

    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    public static function new(array $keys) {
        return new JwksEndpoint($keys);
    }

    public static function fromIssuer(Database $database, $issuer) {
        $registration = $database->findRegistrationByIssuer($issuer);
        return new JwksEndpoint([$registration->getKid() => $registration->getToolPrivateKey()]);
    }

    public static function fromRegistration(LtiRegistrationInterface $registration) {
        return new JwksEndpoint([$registration->getKid() => $registration->getToolPrivateKey()]);
    }

    public function getPublicJwks()
    {
        $jwks = [];
        foreach ($this->keys as $kid => $private_key) {
            $key = new RSA();
            $key->setHash("sha256");
            $key->loadKey($private_key);
            $key->setPublicKey(false, RSA::PUBLIC_FORMAT_PKCS8);
            if ( !$key->publicExponent ) {
                continue;
            }
            $components = array(
                'kty' => 'RSA',
                'alg' => 'RS256',
                'use' => 'sig',
                'e' => JWT::urlsafeB64Encode($key->publicExponent->toBytes()),
                'n' => JWT::urlsafeB64Encode($key->modulus->toBytes()),
                'kid' => $kid,
            );
            $jwks[] = $components;
        }
        return ['keys' => $jwks];
    }

    public function outputJwks()
    {
        echo json_encode($this->getPublicJwks());
    }

}
