<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20210802194737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add collective field to c_calendar_event';
    }

    public function up(Schema $schema): void
    {
        $schema
            ->getTable('c_calendar_event')
            ->addColumn('collective', Types::BOOLEAN)
            ->setDefault(false);
    }

    public function down(Schema $schema): void
    {
        $schema
            ->getTable('c_calendar_event')
            ->dropColumn('collective');
    }
}
