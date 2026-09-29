<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderItemUpdateInput;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class LearningPathBuilderItemAction
{
    public function __invoke(): LearningPathBuilderItemUpdateInput
    {
        return new LearningPathBuilderItemUpdateInput();
    }
}
