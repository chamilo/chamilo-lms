<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Tool;

class Test2Pdf extends AbstractPlugin
{
    public function getTitle(): string
    {
        return 'Test2Pdf';
    }

    public function getLink(): string
    {
        return '/plugin/Test2Pdf/start.php';
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
