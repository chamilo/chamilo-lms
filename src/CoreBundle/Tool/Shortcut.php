<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\LtiBundle\Entity\ExternalTool;

class Shortcut extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'shortcuts';
    }

    public function getIcon(): string
    {
        return 'mdi';
    }

    public function getLink(): string
    {
        return '/';
    }

    public function getCategory(): string
    {
        return 'admin';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'shortcuts' => CShortcut::class,
            'external_tools' => ExternalTool::class,
        ];
    }
}
