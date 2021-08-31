<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CWiki;

class Wiki extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'wiki';
    }

    public function getIcon(): string
    {
        return 'mdi-xml';
    }

    public function getLink(): string
    {
        return '/main/wiki/index.php';
    }

    public function getCategory(): string
    {
        return 'interaction';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'wikis' => CWiki::class,
        ];
    }
}
