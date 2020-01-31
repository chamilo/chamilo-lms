<?php

namespace SAML2\Certificate;

use SAML2\Exception\InvalidArgumentException;
use SAML2\Utilities\ArrayCollection;

/**
 * Simple collection object for transporting keys
 * @deprecated Please load full certificates instead.
 */
class FingerprintCollection extends ArrayCollection
{
    /**
     * Add a key to the collection
     *
     * @param \SAML2\Certificate\Fingerprint $fingerprint
     * @return void
     *
     * @deprecated
     */
    public function add($fingerprint)
    {
        if (!$fingerprint instanceof Fingerprint) {
            throw InvalidArgumentException::invalidType(
                'SAML2\Certificate\Fingerprint ',
                $fingerprint
            );
        }

        parent::add($fingerprint);
    }


    /**
     * @param \SAML2\Certificate\Fingerprint $otherFingerprint
     * @return bool
     *
     * @deprecated
     */
    public function contains(Fingerprint $otherFingerprint)
    {
        foreach ($this->elements as $fingerprint) {
            /** @var \SAML2\Certificate\Fingerprint $fingerprint */
            if ($fingerprint->equals($otherFingerprint)) {
                return true;
            }
        }

        return false;
    }
}
