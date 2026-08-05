<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathConfiguration;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class LearningPathConfigurationAction
{
    public function __invoke(): LearningPathConfiguration
    {
        return new LearningPathConfiguration();
    }
}
