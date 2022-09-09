<?php

declare(strict_types = 1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220909165130 extends AbstractMigrationChamilo
{
    /**
     * Return desription of the migration step.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'change field user_ip length 45 characters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE track_e_exercises CHANGE user_ip user_ip VARCHAR(45) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_course_access CHANGE user_ip user_ip VARCHAR(45) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE room CHANGE ip ip VARCHAR(45) DEFAULT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_access CHANGE user_ip user_ip VARCHAR(45) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_online CHANGE user_ip user_ip VARCHAR(45) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_login CHANGE user_ip user_ip VARCHAR(45) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_login_record CHANGE user_ip user_ip VARCHAR(45) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE c_wiki CHANGE user_ip user_ip VARCHAR(45) NOT NULL;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE c_wiki CHANGE user_ip user_ip VARCHAR(39) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_login_record CHANGE user_ip user_ip VARCHAR(39) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_login CHANGE user_ip user_ip VARCHAR(39) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_online CHANGE user_ip user_ip VARCHAR(39) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_access CHANGE user_ip user_ip VARCHAR(39) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE room CHANGE ip ip VARCHAR(39) DEFAULT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_course_access CHANGE user_ip user_ip VARCHAR(39) NOT NULL;'
        );
        $this->addSql(
            'ALTER TABLE track_e_exercises CHANGE user_ip user_ip VARCHAR(39) NOT NULL;'
        );
    }
}
