<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Tool\ToolChain;
use Doctrine\DBAL\Schema\Schema;

class Version20250927170000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Register the portfolio tool and its resource types';
    }

    public function up(Schema $schema): void
    {
        /** @var ToolChain $toolChain */
        $toolChain = $this->container->get(ToolChain::class);

        $toolChain->createTools();
    }
}
