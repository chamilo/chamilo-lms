<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20180904190000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate promotion';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('promotion');

        $this->addSql('ALTER TABLE promotion CHANGE career_id career_id INT DEFAULT NULL');
        if (!$table->hasForeignKey('FK_C11D7DD1B58CDA09')) {
            $this->addSql('ALTER TABLE promotion ADD CONSTRAINT FK_C11D7DD1B58CDA09 FOREIGN KEY (career_id) REFERENCES career (id);');
        }

        if (!$table->hasIndex('IDX_C11D7DD1B58CDA09')) {
            $this->addSql('CREATE INDEX IDX_C11D7DD1B58CDA09 ON promotion (career_id);');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
