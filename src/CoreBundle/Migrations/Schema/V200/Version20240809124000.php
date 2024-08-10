<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240809124000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove user_id foreign key and column from gradebook_link and gradebook_evaluation tables.';
    }

    public function up(Schema $schema): void
    {
        // Drop foreign key and index from gradebook_link
        $this->addSql('ALTER TABLE gradebook_link DROP FOREIGN KEY FK_4F0F595FA76ED395');
        $this->addSql('DROP INDEX IDX_4F0F595FA76ED395 ON gradebook_link');
        $this->addSql('ALTER TABLE gradebook_link DROP COLUMN user_id');

        // Drop foreign key and index from gradebook_evaluation
        $this->addSql('ALTER TABLE gradebook_evaluation DROP FOREIGN KEY FK_DDDED804A76ED395');
        $this->addSql('DROP INDEX IDX_DDDED804A76ED395 ON gradebook_evaluation');
        $this->addSql('ALTER TABLE gradebook_evaluation DROP COLUMN user_id');
    }

    public function down(Schema $schema): void
    {
        // Re-add the user_id column and foreign key to gradebook_link
        $this->addSql('ALTER TABLE gradebook_link ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_link ADD CONSTRAINT FK_4F0F595FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_4F0F595FA76ED395 ON gradebook_link (user_id)');

        // Re-add the user_id column and foreign key to gradebook_evaluation
        $this->addSql('ALTER TABLE gradebook_evaluation ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_evaluation ADD CONSTRAINT FK_DDDED804A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_DDDED804A76ED395 ON gradebook_evaluation (user_id)');
    }
}
