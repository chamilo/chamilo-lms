<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

use Chamilo\CourseBundle\Entity\CDocument;

class DocumentItemViewHookEvent extends HookEvent
{
    public function getDocument(): ?CDocument
    {
        return $this->data['document'] ?? null;
    }
}
