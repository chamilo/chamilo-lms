<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250320213000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Creates tables for course and session catalogue visibility with access URL and usergroup restrictions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE catalogue_course_rel_access_url_rel_usergroup (
                id INT AUTO_INCREMENT NOT NULL,
                course_id INT DEFAULT NULL,
                access_url_id INT DEFAULT NULL,
                usergroup_id INT DEFAULT NULL,
                INDEX IDX_37CC1F8E591CC992 (course_id),
                INDEX IDX_37CC1F8E73444FD5 (access_url_id),
                INDEX IDX_37CC1F8ED2112630 (usergroup_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
        ');

        $this->addSql('
            CREATE TABLE catalogue_session_rel_access_url_rel_usergroup (
                id INT AUTO_INCREMENT NOT NULL,
                session_id INT DEFAULT NULL,
                access_url_id INT DEFAULT NULL,
                usergroup_id INT DEFAULT NULL,
                INDEX IDX_B143E63A613FECDF (session_id),
                INDEX IDX_B143E63A73444FD5 (access_url_id),
                INDEX IDX_B143E63AD2112630 (usergroup_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
        ');

        $this->addSql('
            ALTER TABLE catalogue_course_rel_access_url_rel_usergroup
            ADD CONSTRAINT FK_37CC1F8E591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE;
        ');

        $this->addSql('
            ALTER TABLE catalogue_course_rel_access_url_rel_usergroup
            ADD CONSTRAINT FK_37CC1F8E73444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE;
        ');

        $this->addSql('
            ALTER TABLE catalogue_course_rel_access_url_rel_usergroup
            ADD CONSTRAINT FK_37CC1F8ED2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id) ON DELETE SET NULL;
        ');

        $this->addSql('
            ALTER TABLE catalogue_session_rel_access_url_rel_usergroup
            ADD CONSTRAINT FK_B143E63A613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE;
        ');

        $this->addSql('
            ALTER TABLE catalogue_session_rel_access_url_rel_usergroup
            ADD CONSTRAINT FK_B143E63A73444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE;
        ');

        $this->addSql('
            ALTER TABLE catalogue_session_rel_access_url_rel_usergroup
            ADD CONSTRAINT FK_B143E63AD2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id) ON DELETE SET NULL;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS catalogue_session_rel_access_url_rel_usergroup;');
        $this->addSql('DROP TABLE IF EXISTS catalogue_course_rel_access_url_rel_usergroup;');
    }
}
