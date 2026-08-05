<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathScormImport;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class LearningPathScormImportAction
{
    public function __invoke(): LearningPathScormImport
    {
        return new LearningPathScormImport();
    }
}
