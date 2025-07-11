<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class NotAllowedException extends HttpException
{
    private string $severity;

    public function __construct(
        string $message = 'Not allowed',
        string $severity = 'warning',
        int $statusCode = 403,
        array $headers = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->severity = $severity;
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }
}
