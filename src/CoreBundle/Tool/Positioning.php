<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Tool;

class Positioning extends AbstractPlugin
{
    public function getTitle(): string
    {
        return 'Positioning';
    }

    public function getLink(): string
    {
        return '/plugin/Positioning/start.php';
    }

    public function getIcon(): string
    {
        return 'mdi-radar';
    }
}
