<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class Tracking extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'tracking';
    }

    public function getNameToShow(): string
    {
        return 'Reporting';
    }

    public function getIcon(): string
    {
        return 'mdi-google-analytics';
    }

    public function getLink(): string
    {
        return '/main/tracking/courseLog.php';
    }

    public function getCategory(): string
    {
        return 'interaction';
    }
}
