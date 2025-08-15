<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;

class LearningPath extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'learnpath';
    }

    public function getTitleToShow(): string
    {
        return 'Learning paths';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getLink(): string
    {
        return '/resources/lp/:nodeId/';
    }

    public function getIcon(): string
    {
        return 'mdi-map-marker-path';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'lps' => CLp::class,
            'lp_categories' => CLpCategory::class,
        ];
    }
}
