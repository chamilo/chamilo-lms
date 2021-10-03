<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class VideoConference extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'bbb';
    }

    public function getNameToShow(): string
    {
        return 'Videoconference';
    }

    public function getIcon(): string
    {
        return 'mdi-video';
    }

    public function getLink(): string
    {
        return '/plugin/bbb/start.php';
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
