<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;

class LearningPath extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'learnpath';
    }

    public function getNameToShow(): string
    {
        return 'Learning paths';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getLink(): string
    {
        return '/main/lp/lp_controller.php';
    }

    public function getIcon(): string
    {
        return 'mdi-routes';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'lps' => CLp::class,
            'lp_categories' => CLpCategory::class,
        ];
    }
}
