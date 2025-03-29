<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class VideoConference extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'Bbb';
    }

    public function getTitleToShow(): string
    {
        return 'Videoconference';
    }

    public function getIcon(): string
    {
        return 'mdi-video';
    }

    public function getLink(): string
    {
        return '/plugin/Bbb/start.php';
    }

    public function getCategory(): string
    {
        return 'plugin';
    }

    public function getResourceTypes(): ?array
    {
        return [
        ];
    }
}
