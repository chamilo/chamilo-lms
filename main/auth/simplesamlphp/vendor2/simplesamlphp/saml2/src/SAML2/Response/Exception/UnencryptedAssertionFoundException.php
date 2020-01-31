<?php

namespace SAML2\Response\Exception;

use SAML2\Exception\Throwable;

class UnencryptedAssertionFoundException extends \RuntimeException implements
    Throwable
{
}
