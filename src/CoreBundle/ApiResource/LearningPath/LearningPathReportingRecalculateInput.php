<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use Symfony\Component\Serializer\Attribute\Groups;

final class LearningPathReportingRecalculateInput
{
    #[Groups(['learning_path_reporting_recalculate:write'])]
    public int $userId = 0;

    #[Groups(['learning_path_reporting_recalculate:write'])]
    public string $csrfToken = '';
}
