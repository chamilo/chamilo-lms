<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use Symfony\Component\Serializer\Attribute\Groups;

final class SessionPlanItem
{
    #[Groups(['session_plan:read'])]
    public int $id;

    #[Groups(['session_plan:read'])]
    public string $title;

    #[Groups(['session_plan:read'])]
    public ?string $startDate = null; // YYYY-MM-DD

    #[Groups(['session_plan:read'])]
    public ?string $endDate = null; // YYYY-MM-DD

    #[Groups(['session_plan:read'])]
    public ?string $humanDate = null;

    // Week index 0..51 (C1: start = week-1)
    #[Groups(['session_plan:read'])]
    public int $start = 0;

    // Duration in weeks (>=1)
    #[Groups(['session_plan:read'])]
    public int $duration = 1;

    #[Groups(['session_plan:read'])]
    public bool $startInLastYear = false;

    #[Groups(['session_plan:read'])]
    public bool $endInNextYear = false;

    #[Groups(['session_plan:read'])]
    public bool $noStart = false;

    #[Groups(['session_plan:read'])]
    public bool $noEnd = false;

    // rgba(...) from ChamiloApi palette (same as C1)
    #[Groups(['session_plan:read'])]
    public ?string $color = null;
}
