<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use Symfony\Component\Serializer\Attribute\Groups;

final class LearningPathLayoutInput
{
    /**
     * @var list<array{id: int, learningPathIds: list<int>}>
     */
    #[Groups(['lp:layout'])]
    public array $categories = [];

    /**
     * @var list<int>
     */
    #[Groups(['lp:layout'])]
    public array $uncategorized = [];

    #[Groups(['lp:layout'])]
    public ?string $csrfToken = null;
}
