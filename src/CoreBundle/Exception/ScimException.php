<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Exception;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ScimException extends RuntimeException
{
    public function __construct(
        string $message = '',
        private readonly int $statusCode = Response::HTTP_BAD_REQUEST,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
