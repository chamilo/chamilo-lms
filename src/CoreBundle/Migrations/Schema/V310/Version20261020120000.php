<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261020120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add c_student_publication_follow (teachers following an assignment) and make each existing assignment\'s creator a follower';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_student_publication_follow')) {
            return;
        }

        $this->addSql(<<<'SQL'
        CREATE TABLE c_student_publication_follow (
          id INT AUTO_INCREMENT NOT NULL,
          publication_id INT NOT NULL,
          user_id INT NOT NULL,
          created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
          INDEX IDX_8FCA0D138B217A7 (publication_id),
          INDEX IDX_8FCA0D1A76ED395 (user_id),
          UNIQUE INDEX uniq_student_publication_follow (publication_id, user_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        SQL);

        $this->addSql('ALTER TABLE c_student_publication_follow ADD CONSTRAINT FK_8FCA0D138B217A7 FOREIGN KEY (publication_id) REFERENCES c_student_publication (iid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE c_student_publication_follow ADD CONSTRAINT FK_8FCA0D1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // Before this table, every teacher of the course was e-mailed about learner comments.
        // Only the creator of an assignment is known for sure to care about it, so the creator
        // (c_student_publication.user_id of the top-level folder) becomes its follower; other
        // teachers opt in with the Follow icon. Without this, existing assignments would
        // silently stop sending any e-mail after the upgrade.
        $this->addSql(<<<'SQL'
        INSERT INTO c_student_publication_follow (publication_id, user_id, created_at)
        SELECT p.iid, p.user_id, NOW()
        FROM c_student_publication p
        INNER JOIN user u ON u.id = p.user_id
        WHERE p.filetype = 'folder'
          AND p.parent_id IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_student_publication_follow')) {
            $this->addSql('DROP TABLE c_student_publication_follow');
        }
    }
}
