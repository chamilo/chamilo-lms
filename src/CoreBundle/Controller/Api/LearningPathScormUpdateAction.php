<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathScormUpdate;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class LearningPathScormUpdateAction
{
    public function __invoke(): LearningPathScormUpdate
    {
        return new LearningPathScormUpdate();
    }
}
