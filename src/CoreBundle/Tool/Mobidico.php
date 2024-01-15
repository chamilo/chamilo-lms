<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class Mobidico extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'mobidico';
    }

    public function getTitleToShow(): string
    {
        return 'Mobidico';
    }

    public function getIcon(): string
    {
        return 'mdi-book-alphabet';
    }

    public function getLink(): string
    {
        return '/plugin/mobidico/start.php';
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
