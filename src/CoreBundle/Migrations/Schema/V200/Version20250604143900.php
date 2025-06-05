<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250604143900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create third_party, third_party_data_exchange and third_party_data_exchange_user tables for GDPR compliance.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE third_party (
                id INT AUTO_INCREMENT NOT NULL,
                name TEXT NOT NULL,
                description TEXT DEFAULT NULL,
                address TEXT DEFAULT NULL,
                website TEXT DEFAULT NULL,
                data_exchange_party TINYINT(1) NOT NULL,
                recruiter TINYINT(1) NOT NULL,
                PRIMARY KEY(id)
            );
        ");

        $this->addSql("
            CREATE TABLE third_party_data_exchange (
                id INT AUTO_INCREMENT NOT NULL,
                third_party_id INT NOT NULL,
                sent_at DATETIME NOT NULL,
                description TEXT DEFAULT NULL,
                all_users TINYINT(1) NOT NULL,
                PRIMARY KEY(id),
                CONSTRAINT FK_TPDE_TP FOREIGN KEY (third_party_id) REFERENCES third_party(id) ON DELETE CASCADE
            );
        ");

        $this->addSql("
            CREATE TABLE third_party_data_exchange_user (
                id INT AUTO_INCREMENT NOT NULL,
                third_party_data_exchange_id INT NOT NULL,
                user_id INT NOT NULL,
                PRIMARY KEY(id),
                CONSTRAINT FK_TPDEU_TPDE FOREIGN KEY (third_party_data_exchange_id) REFERENCES third_party_data_exchange(id) ON DELETE CASCADE,
                CONSTRAINT FK_TPDEU_USER FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            );
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE third_party_data_exchange_user;');
        $this->addSql('DROP TABLE third_party_data_exchange;');
        $this->addSql('DROP TABLE third_party;');
    }
}
