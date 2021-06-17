<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20200922224343 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'sys_announcement changes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('sys_announcement');

        if (!$table->hasColumn('roles')) {
            $this->addSql("ALTER TABLE sys_announcement ADD roles LONGTEXT NOT NULL COMMENT '(DC2Type:array)'");
        }

        if (!$table->hasColumn('career_id')) {
            $this->addSql('ALTER TABLE sys_announcement ADD career_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE sys_announcement ADD CONSTRAINT FK_E4A3EAD4B58CDA09 FOREIGN KEY (career_id) REFERENCES career (id)  ON DELETE CASCADE');
        } else {
            if (!$table->hasForeignKey('FK_E4A3EAD4B58CDA09')) {
                $this->addSql('ALTER TABLE sys_announcement ADD CONSTRAINT FK_E4A3EAD4B58CDA09 FOREIGN KEY (career_id) REFERENCES career (id)  ON DELETE CASCADE');
            }
        }

        if (!$table->hasColumn('promotion_id')) {
            $this->addSql('ALTER TABLE sys_announcement ADD promotion_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE sys_announcement ADD CONSTRAINT FK_E4A3EAD4139DF194 FOREIGN KEY (promotion_id) REFERENCES promotion (id)  ON DELETE CASCADE');
        } else {
            if (!$table->hasForeignKey('FK_E4A3EAD4139DF194')) {
                $this->addSql('ALTER TABLE sys_announcement ADD CONSTRAINT FK_E4A3EAD4139DF194 FOREIGN KEY (promotion_id) REFERENCES promotion (id)  ON DELETE CASCADE');
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
