<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Forum;

final class ForumThreadGradingInput
{
    public ?bool $enabled = null;

    public ?int $categoryId = null;

    public ?float $maxScore = null;

    public ?float $weight = null;

    public ?string $title = null;

    public ?bool $peerQualify = null;

    public ?string $csrfToken = null;
}
