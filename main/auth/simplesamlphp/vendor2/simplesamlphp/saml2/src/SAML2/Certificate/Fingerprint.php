<?php

namespace SAML2\Certificate;

use SAML2\Exception\InvalidArgumentException;

/**
 * Simple representation of the fingerprint of a certificate
 *
 * @deprecated Please use full certificates instead.
 */
class Fingerprint
{
    /**
     * @var string
     */
    private $contents;


    /**
     * @param string $fingerPrint
     *
     * @deprecated Please use full certificates instead.
     */
    public function __construct($fingerPrint)
    {
        if (!is_string($fingerPrint)) {
            throw InvalidArgumentException::invalidType('string', $fingerPrint);
        }

        $this->contents = $fingerPrint;
    }


    /**
     * Get the raw, unmodified fingerprint value.
     *
     * @return string
     */
    public function getRaw()
    {
        return $this->contents;
    }


    /**
     * @return string
     */
    public function getNormalized()
    {
        return strtolower(str_replace(':', '', $this->contents));
    }


    /**
     * @param \SAML2\Certificate\Fingerprint $fingerprint
     * @return bool
     */
    public function equals(Fingerprint $fingerprint)
    {
        return $this->getNormalized() === $fingerprint->getNormalized();
    }
}
