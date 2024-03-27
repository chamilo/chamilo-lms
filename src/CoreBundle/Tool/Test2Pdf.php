<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Tool;

class Test2Pdf extends AbstractPlugin
{
    public function getTitle(): string
    {
        return 'test2pdf';
    }

    public function getLink(): string
    {
        return '/plugin/test2pdf/start.php';
    }

    public function getIcon(): string
    {
        return 'mdi-file-pdf-box';
    }

    public function getTitleToShow(): string
    {
        return 'Test to Pdf (Test2Pdf)';
    }
}
