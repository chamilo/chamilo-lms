<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20230321154019 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create table track_e_attempt_qualify';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('track_e_attempt_qualify')) {
            $this->addSql(
            "CREATE TABLE track_e_attempt_qualify (
                id INT AUTO_INCREMENT NOT NULL,
                exe_id INT NOT NULL,
                question_id INT NOT NULL,
                marks INT NOT NULL,
                insert_date DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                author INT NOT NULL,
                teacher_comment LONGTEXT NOT NULL,
                session_id INT NOT NULL,
                answer LONGTEXT DEFAULT NULL,
                INDEX exe_id (exe_id), INDEX question_id (question_id),
                INDEX session_id (session_id),
                PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;"
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('track_e_attempt_qualify')) {
            $this->addSql('DROP TABLE track_e_attempt_qualify;');
        }
    }
}
