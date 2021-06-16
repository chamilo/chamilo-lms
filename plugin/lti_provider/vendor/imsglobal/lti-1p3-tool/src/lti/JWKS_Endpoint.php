<?php
namespace IMSGlobal\LTI;

use phpseclib\Crypt\RSA;
use \Firebase\JWT\JWT;

class JWKS_Endpoint {

    private $keys;

    public function __construct(array $keys) {
        $this->keys = $keys;
    }

    public static function new($keys) {
        return new JWKS_Endpoint($keys);
    }

    public static function from_issuer(Database $database, $issuer) {
        $registration = $database->find_registration_by_issuer($issuer);
        return new JWKS_Endpoint([$registration->get_kid() => $registration->get_tool_private_key()]);
    }

    public static function from_registration(LTI_Registration $registration) {
        return new JWKS_Endpoint([$registration->get_kid() => $registration->get_tool_private_key()]);
    }

    public function get_public_jwks() {
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

    public function output_jwks() {
        echo json_encode($this->get_public_jwks());
    }

}