<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Conference;

use Symfony\Component\Serializer\Attribute\Groups;

final class TeamsMeetingCreateInput
{
    #[Groups(['teams_meeting:create'])]
    public string $scope = 'course';

    #[Groups(['teams_meeting:create'])]
    public string $title = '';

    /**
     * ISO-8601 date/time. Leave both dates empty to start now for one hour.
     */
    #[Groups(['teams_meeting:create'])]
    public ?string $startAt = null;

    #[Groups(['teams_meeting:create'])]
    public ?string $endAt = null;

    #[Groups(['teams_meeting:create'])]
    public bool $addToCalendar = false;
}
