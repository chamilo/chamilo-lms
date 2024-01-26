<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use DocumentManager;

trait GradebookControllerTrait
{
    private function getAllInfoToCertificate(int $userId, int $courseId, int $sessionId, bool $isPreview = false): array
    {
        return DocumentManager::get_all_info_to_certificate(
            $userId,
            $courseId,
            $sessionId,
            $isPreview
        );
    }
}
