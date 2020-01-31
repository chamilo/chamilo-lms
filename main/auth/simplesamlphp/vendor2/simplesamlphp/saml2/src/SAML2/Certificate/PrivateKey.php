<?php

namespace SAML2\Certificate;

use SAML2\Exception\InvalidArgumentException;

class PrivateKey extends Key
{
    /**
     * @param string $keyContents
     * @param string|null $passphrase
     * @throws InvalidArgumentException
     * @return PrivateKey
     */
    public static function create($keyContents, $passphrase = null)
    {
        if (!is_string($keyContents)) {
            throw InvalidArgumentException::invalidType('string', $keyContents);
        }

        if ($passphrase && !is_string($passphrase)) {
            throw InvalidArgumentException::invalidType('string', $passphrase);
        }

        $keyData = ['PEM' => $keyContents, self::USAGE_ENCRYPTION => true];
        if ($passphrase) {
            $keyData['passphrase'] = $passphrase;
        }

        return new self($keyData);
    }


    /**
     * @return string
     */
    public function getKeyAsString()
    {
        return $this->keyData['PEM'];
    }


    /**
     * @return string|null
     */
    public function getPassphrase()
    {
        return isset($this->keyData['passphrase']) ? $this->keyData['passphrase'] : null;
    }
}
