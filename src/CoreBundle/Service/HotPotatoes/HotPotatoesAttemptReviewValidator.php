<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\HotPotatoes;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class HotPotatoesAttemptReviewValidator
{
    /**
     * @param array<string, mixed> $payload
     */
    public function validate(array $payload): int
    {
        if (array_keys($payload) !== ['scorePercent']
            || !\is_int($payload['scorePercent'])
            || $payload['scorePercent'] < 0
            || $payload['scorePercent'] > 100
        ) {
            throw new BadRequestHttpException('The reviewed score must be an integer between 0 and 100.');
        }

        return $payload['scorePercent'];
    }
}
