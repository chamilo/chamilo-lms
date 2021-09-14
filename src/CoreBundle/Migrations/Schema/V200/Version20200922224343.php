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
            $this->addSql('UPDATE sys_announcement SET roles = "a:0:{}"');
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

        if (!$table->hasIndex('IDX_E4A3EAD4139DF194')) {
            $this->addSql(' CREATE INDEX IDX_E4A3EAD4139DF194 ON sys_announcement (promotion_id);');
        }

        if (!$table->hasIndex('IDX_E4A3EAD4B58CDA09')) {
            $this->addSql(' CREATE INDEX IDX_E4A3EAD4B58CDA09 ON sys_announcement (career_id);');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
