<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20201212195110 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Session changes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('session');
        if (false === $table->hasColumn('image_id')) {
            $this->addSql("ALTER TABLE session ADD image_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)'");
            $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D43DA5256D FOREIGN KEY (image_id) REFERENCES asset (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_D044D5D43DA5256D ON session (image_id)');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
