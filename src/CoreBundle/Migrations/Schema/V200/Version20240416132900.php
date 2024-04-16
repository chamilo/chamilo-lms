<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240416132900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update mail_template table structure, adding author_id, changing url_id and removing old fields';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('mail_template')) {
            // Adding author_id and setting the foreign key
            $this->addSql('ALTER TABLE mail_template ADD author_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE mail_template ADD CONSTRAINT FK_4AB7DECBF675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_4AB7DECBF675F31B ON mail_template (author_id)');

            // Updating result_id to url_id and adjusting foreign key
            $this->addSql('ALTER TABLE mail_template CHANGE result_id url_id INT NOT NULL');
            $this->addSql('ALTER TABLE mail_template ADD CONSTRAINT FK_4AB7DECB81CFDAE7 FOREIGN KEY (url_id) REFERENCES access_url (id) ON DELETE CASCADE');
            $this->addSql('CREATE INDEX IDX_4AB7DECB81CFDAE7 ON mail_template (url_id)');

            // Dropping unused column 'score'
            $this->addSql('ALTER TABLE mail_template DROP score');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('mail_template')) {
            // Reverting changes
            $this->addSql('ALTER TABLE mail_template DROP FOREIGN KEY FK_4AB7DECBF675F31B');
            $this->addSql('ALTER TABLE mail_template DROP author_id');
            $this->addSql('DROP INDEX IDX_4AB7DECBF675F31B ON mail_template');

            $this->addSql('ALTER TABLE mail_template DROP FOREIGN KEY FK_4AB7DECB81CFDAE7');
            $this->addSql('ALTER TABLE mail_template CHANGE url_id result_id INT NOT NULL');
            $this->addSql('DROP INDEX IDX_4AB7DECB81CFDAE7 ON mail_template');

            $this->addSql('ALTER TABLE mail_template ADD score FLOAT NULL DEFAULT NULL');
        }
    }
}
