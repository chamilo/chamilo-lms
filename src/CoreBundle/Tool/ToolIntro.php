<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CToolIntro;

class ToolIntro extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'ctoolintro';
    }

    public function getIcon(): string
    {
        return 'mdi-certificate';
    }

    public function getLink(): string
    {
        return '/resources/ctoolintro';
    }

    public function getCategory(): string
    {
        return 'tool';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'ctoolintro' => CToolIntro::class,
        ];
    }
}
