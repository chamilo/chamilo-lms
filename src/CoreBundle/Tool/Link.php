<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;

class Link extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'link';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getIcon(): string
    {
        return 'mdi-file-link';
    }

    public function getLink(): string
    {
        return '/main/link/link.php';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'links' => CLink::class,
            'link_categories' => CLinkCategory::class,
        ];
    }
}
