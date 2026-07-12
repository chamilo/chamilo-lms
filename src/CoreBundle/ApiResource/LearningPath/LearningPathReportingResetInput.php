<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use Symfony\Component\Serializer\Attribute\Groups;

final class LearningPathReportingResetInput
{
    /** @var array<int, int|string> */
    #[Groups(['learning_path_reporting_reset:write'])]
    public array $userIds = [];

    #[Groups(['learning_path_reporting_reset:write'])]
    public bool $deleteExerciseAttempts = false;

    #[Groups(['learning_path_reporting_reset:write'])]
    public string $csrfToken = '';
}
