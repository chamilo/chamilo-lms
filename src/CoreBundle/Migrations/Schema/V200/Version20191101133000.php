<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20191101133000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create and set up the user_career table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('user_career')) {
            $this->addSql('CREATE TABLE user_career (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, career_id INT NOT NULL, extra_data LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_D70977B9A76ED395 (user_id), INDEX IDX_D70977B9B58CDA09 (career_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;');
            $this->addSql('ALTER TABLE user_career ADD CONSTRAINT FK_D70977B9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE user_career ADD CONSTRAINT FK_D70977B9B58CDA09 FOREIGN KEY (career_id) REFERENCES career (id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('user_career')) {
            $this->addSql('DROP TABLE user_career');
        }
    }
}
