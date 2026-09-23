<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CToolbox;

final class Toolbox extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'toolbox';
    }

    public function getTitleToShow(): string
    {
        return 'Toolbox';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getLink(): string
    {
        return '/resources/toolbox/:nodeId/';
    }

    public function getIcon(): string
    {
        return 'mdi-tools';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'toolbox_items' => CToolbox::class,
        ];
    }
}
