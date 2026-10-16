<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Conference;

use Symfony\Component\Serializer\Attribute\Groups;

final class TeamsMeetingUpdateInput
{
    #[Groups(['teams_meeting:update'])]
    public ?string $title = null;

    #[Groups(['teams_meeting:update'])]
    public ?string $startAt = null;

    #[Groups(['teams_meeting:update'])]
    public ?string $endAt = null;
}
