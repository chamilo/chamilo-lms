<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\ApiResource\HotPotatoes\HotPotatoesArchiveImport;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class HotPotatoesArchiveImportAction
{
    public function __invoke(): HotPotatoesArchiveImport
    {
        return new HotPotatoesArchiveImport();
    }
}
