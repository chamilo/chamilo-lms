<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class Tracking extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'tracking';
    }

    public function getTitleToShow(): string
    {
        return 'Reporting';
    }

    public function getIcon(): string
    {
        return 'mdi-chart-box';
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
