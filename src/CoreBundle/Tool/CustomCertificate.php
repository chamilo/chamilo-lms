<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Tool;

class CustomCertificate extends AbstractPlugin
{
    public function getTitle(): string
    {
        return 'CustomCertificate';
    }

    public function getLink(): string
    {
        return '/plugin/CustomCertificate/start.php';
    }

    public function getIcon(): string
    {
        return 'mdi-certificate-outline';
    }

    public function getTitleToShow(): string
    {
        return 'Custom certificate';
    }
}
