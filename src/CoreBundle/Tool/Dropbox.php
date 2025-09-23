<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CDropboxFile;

class Dropbox extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'dropbox';
    }

    public function getIcon(): string
    {
        return 'mdi-dropbox';
    }

    public function getLink(): string
    {
        return '/resources/dropbox/:nodeId/';
    }

    public function getCategory(): string
    {
        return 'interaction';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'dropbox' => CDropboxFile::class,
        ];
    }
}
