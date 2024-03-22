<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class NotAllowedException extends HttpException
{
    public function __construct(?string $message = 'Not allowed', ?Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(Response::HTTP_FORBIDDEN, $message, $previous, $headers, $code);
    }
}
