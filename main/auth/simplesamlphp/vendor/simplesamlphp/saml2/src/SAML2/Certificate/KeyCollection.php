<?php

namespace SAML2\Certificate;

use SAML2\Exception\InvalidArgumentException;
use SAML2\Utilities\ArrayCollection;

/**
 * Simple collection object for transporting keys
 */
class KeyCollection extends ArrayCollection
{
    /**
     * Add a key to the collection
     *
     * @param \SAML2\Certificate\Key $key
     * @return void
     */
    public function add($key)
    {
        if (!($key instanceof Key)) {
            throw InvalidArgumentException::invalidType(
                'SAML2\Certificate\Key',
                $key
            );
        }

        parent::add($key);
    }
}
