<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupCategory;

class Group extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'group';
    }

    public function getNameToShow(): string
    {
        return 'Groups';
    }

    public function getLink(): string
    {
        return '/main/group/group.php';
    }

    public function getIcon(): string
    {
        return 'mdi-account-group';
    }

    public function getCategory(): string
    {
        return 'interaction';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'groups' => CGroup::class,
            'group_categories' => CGroupCategory::class,
        ];
    }
}
