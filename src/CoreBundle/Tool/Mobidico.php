<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Tool;

class Mobidico extends AbstractPlugin
{
    public function getTitle(): string
    {
        return 'Mobidico';
    }

    public function getTitleToShow(): string
    {
        return 'Mobidico';
    }

    public function getLink(): string
    {
        return '/plugin/Mobidico/start.php';
    }

    public function getIcon(): string
    {
        return 'mdi-book-alphabet';
    }
}
