<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CDocument;

class Document extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'document';
    }

    public function getTitleToShow(): string
    {
        return 'Documents';
    }

    public function getIcon(): string
    {
        return 'mdi-bookshelf';
    }

    public function getLink(): string
    {
        return '/resources/document/:nodeId/';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'files' => CDocument::class,
        ];
    }
}
