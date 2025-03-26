<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240204120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove track_course_ranking, add popularity to course.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course ADD COLUMN popularity INT NOT NULL DEFAULT 0');

        $this->addSql('DROP TABLE IF EXISTS track_course_ranking');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course DROP COLUMN popularity');

        $this->addSql('
        CREATE TABLE track_course_ranking (
            id INT AUTO_INCREMENT NOT NULL,
            c_id INT NOT NULL,
            session_id INT NOT NULL,
            url_id INT NOT NULL,
            accesses INT NOT NULL,
            total_score INT NOT NULL,
            users INT NOT NULL,
            creation_date DATETIME NOT NULL,
            PRIMARY KEY(id)
        )
    ');
    }
}
