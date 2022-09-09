<?php

declare(strict_types = 1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220628180435 extends AbstractMigrationChamilo
{
    /**
     * Return desription of the migration step.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'track login record';
    }

    public function up(Schema $schema): void
    {
        if (false === $schema->hasTable('track_e_login_record')) {
            $this->addSql(
                'CREATE TABLE track_e_login_record (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(100) NOT NULL, login_date DATETIME NOT NULL COMMENT "(DC2Type:datetime)", user_ip VARCHAR(39) NOT NULL, success TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
