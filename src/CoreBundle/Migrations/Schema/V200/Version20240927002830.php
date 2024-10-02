<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240927002830 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create ticket_rel_user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE ticket_rel_user (
                user_id INT NOT NULL,
                ticket_id INT NOT NULL,
                notify TINYINT(1) NOT NULL,
                PRIMARY KEY(user_id, ticket_id),
                CONSTRAINT FK_ticket_rel_user_user_id FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
                CONSTRAINT FK_ticket_rel_user_ticket_id FOREIGN KEY (ticket_id) REFERENCES ticket_ticket (id) ON DELETE CASCADE
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ticket_rel_user');
    }
}
