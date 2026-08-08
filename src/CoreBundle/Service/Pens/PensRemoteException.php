<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Pens;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Throwable;

#[Exclude]
final class PensRemoteException extends RuntimeException
{
    public const int INVALID_URL = 1301;
    public const int RETRIEVAL_FAILED = 1310;
    public const int ACCESS_DENIED = 1312;
    public const int STORAGE_FAILURE = 1440;
    public const int CALLBACK_FAILED = 1500;

    public function __construct(
        private readonly int $pensErrorCode,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $pensErrorCode, $previous);
    }

    public function getPensErrorCode(): int
    {
        return $this->pensErrorCode;
    }
}
