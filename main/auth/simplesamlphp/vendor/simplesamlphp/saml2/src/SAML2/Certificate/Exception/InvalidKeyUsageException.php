<?php

namespace SAML2\Certificate\Exception;

use SAML2\Certificate\Key;
use SAML2\Exception\Throwable;

/**
 * Named exception for when a non-existent key-usage is given
 */
class InvalidKeyUsageException extends \InvalidArgumentException implements
    Throwable
{
    /**
     * @param string $usage
     */
    public function __construct($usage)
    {
        $message = sprintf(
            'Invalid key usage given: "%s", usages "%s" allowed',
            is_string($usage) ? $usage : gettype($usage),
            implode('", "', Key::getValidKeyUsages())
        );

        parent::__construct($message);
    }
}
