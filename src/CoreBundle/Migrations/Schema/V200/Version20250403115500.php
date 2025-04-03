<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250403115500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add access_url_id to ticket_priority, ticket_project, ticket_status, and ticket_ticket tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE ticket_ticket ADD access_url_id INT DEFAULT NULL;");
        $this->addSql("ALTER TABLE ticket_ticket ADD CONSTRAINT FK_EDE2C76873444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id);");
        $this->addSql("CREATE INDEX IDX_EDE2C76873444FD5 ON ticket_ticket (access_url_id);");

        $this->addSql("ALTER TABLE ticket_status ADD access_url_id INT DEFAULT NULL;");
        $this->addSql("ALTER TABLE ticket_status ADD CONSTRAINT FK_1420FD773444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id);");
        $this->addSql("CREATE INDEX IDX_1420FD773444FD5 ON ticket_status (access_url_id);");

        $this->addSql("ALTER TABLE ticket_project ADD access_url_id INT DEFAULT NULL;");
        $this->addSql("ALTER TABLE ticket_project ADD CONSTRAINT FK_237F89BC73444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id);");
        $this->addSql("CREATE INDEX IDX_237F89BC73444FD5 ON ticket_project (access_url_id);");

        $this->addSql("ALTER TABLE ticket_priority ADD access_url_id INT DEFAULT NULL;");
        $this->addSql("ALTER TABLE ticket_priority ADD CONSTRAINT FK_E7CF20A673444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id);");
        $this->addSql("CREATE INDEX IDX_E7CF20A673444FD5 ON ticket_priority (access_url_id);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE ticket_ticket DROP FOREIGN KEY FK_EDE2C76873444FD5;");
        $this->addSql("DROP INDEX IDX_EDE2C76873444FD5 ON ticket_ticket;");
        $this->addSql("ALTER TABLE ticket_ticket DROP COLUMN access_url_id;");

        $this->addSql("ALTER TABLE ticket_status DROP FOREIGN KEY FK_1420FD773444FD5;");
        $this->addSql("DROP INDEX IDX_1420FD773444FD5 ON ticket_status;");
        $this->addSql("ALTER TABLE ticket_status DROP COLUMN access_url_id;");

        $this->addSql("ALTER TABLE ticket_project DROP FOREIGN KEY FK_237F89BC73444FD5;");
        $this->addSql("DROP INDEX IDX_237F89BC73444FD5 ON ticket_project;");
        $this->addSql("ALTER TABLE ticket_project DROP COLUMN access_url_id;");

        $this->addSql("ALTER TABLE ticket_priority DROP FOREIGN KEY FK_E7CF20A673444FD5;");
        $this->addSql("DROP INDEX IDX_E7CF20A673444FD5 ON ticket_priority;");
        $this->addSql("ALTER TABLE ticket_priority DROP COLUMN access_url_id;");
    }
}
