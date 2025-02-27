<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250221113400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Extend the user.auth_source field';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_auth_source (id INT AUTO_INCREMENT NOT NULL, url_id INT NOT NULL, user_id INT NOT NULL, authentication VARCHAR(255) NOT NULL, INDEX IDX_D632110481CFDAE7 (url_id), INDEX IDX_D6321104A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('ALTER TABLE user_auth_source ADD CONSTRAINT FK_D632110481CFDAE7 FOREIGN KEY (url_id) REFERENCES access_url (id)');
        $this->addSql('ALTER TABLE user_auth_source ADD CONSTRAINT FK_D6321104A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');

        $rows = $this->connection
            ->executeQuery(
                'SELECT u.id user_id, u.auth_source authentication, uru.access_url_id url_id
                    FROM user u LEFT JOIN  access_url_rel_user uru ON u.id = uru.user_id'
            )
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $row['url_id'] ??= 1;

            $this->addSql(
                'INSERT INTO user_auth_source (user_id, authentication, url_id) VALUES (:user_id, :authentication, :url_id)',
                $row
            );
        }

        $this->addSql('ALTER TABLE user DROP auth_source');
    }
}
