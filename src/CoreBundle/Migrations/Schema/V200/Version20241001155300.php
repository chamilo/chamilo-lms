<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20241001155300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Modify tables c_lp_rel_user and c_student_publication, adding new fields for group handling and publication categorization.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE c_lp_rel_user
            ADD IF NOT EXISTS group_id INT NOT NULL,
            ADD IF NOT EXISTS start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
            ADD IF NOT EXISTS end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
            ADD IF NOT EXISTS is_open_without_date TINYINT(1) DEFAULT 0 NOT NULL
        ");

        $this->addSql('
            CREATE INDEX IF NOT EXISTS IDX_AD97516EFE54D947 ON c_lp_rel_user (group_id)
        ');

        $this->addSql('
            ALTER TABLE c_lp_rel_user
            MODIFY group_id INT DEFAULT NULL
        ');

        $this->addSql('
            UPDATE c_lp_rel_user
            SET group_id = NULL
            WHERE group_id = 0
        ');

        $this->addSql('
            ALTER TABLE c_lp_rel_user
            ADD CONSTRAINT FK_AD97516EFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE
        ');

        $this->addSql('
            ALTER TABLE c_student_publication
            ADD IF NOT EXISTS group_category_work_id INT DEFAULT 0
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE c_lp_rel_user
            DROP IF EXISTS group_id,
            DROP IF EXISTS start_date,
            DROP IF EXISTS end_date,
            DROP IF EXISTS is_open_without_date
        ');

        $this->addSql('
            ALTER TABLE c_student_publication
            DROP IF EXISTS group_category_work_id
        ');
    }
}
