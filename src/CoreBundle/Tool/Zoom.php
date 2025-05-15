<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Tool;

class Zoom extends AbstractPlugin
{
    public function getTitle(): string
    {
        return 'Zoom';
    }

    public function getLink(): string
    {
        return 'plugin/Zoom/start.php';
    }

    public function getIcon(): string
    {
        return 'mdi-video-box';
    }

    public function getTitleToShow(): string
    {
        return 'Zoom Videoconference';
    }
}
