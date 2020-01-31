<?php

namespace SAML2\Configuration;

/**
 * Interface \SAML2\Configuration\EntityIdProvider
 */
interface EntityIdProvider
{
    /**
     * @return null|string
     */
    public function getEntityId();
}
