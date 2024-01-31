<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201212114909 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add image_id field to templates table to link with asset table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE templates ADD image_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE templates ADD CONSTRAINT FK_6F287D8E3DA5256D FOREIGN KEY (image_id) REFERENCES asset (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_6F287D8E3DA5256D ON templates (image_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE templates DROP FOREIGN KEY FK_6F287D8E3DA5256D');
        $this->addSql('DROP INDEX IDX_6F287D8E3DA5256D ON templates');
        $this->addSql('ALTER TABLE templates DROP image_id');
    }
}
