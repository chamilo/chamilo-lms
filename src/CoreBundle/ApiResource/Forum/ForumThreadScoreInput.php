<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Forum;

final class ForumThreadScoreInput
{
    public ?int $userId = null;

    public ?float $score = null;

    public ?string $csrfToken = null;
}
