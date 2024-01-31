<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240114215900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Modify track_e_downloads table structure';
    }

    public function up(Schema $schema): void
    {
        $tblTrackDownloads = $schema->getTable('track_e_downloads');

        if ($tblTrackDownloads->hasIndex('idx_ted_c_id')) {
            $this->addSql('DROP INDEX idx_ted_c_id ON track_e_downloads;');
        }

        $this->addSql('ALTER TABLE track_e_downloads ADD resource_link_id INT DEFAULT NULL, DROP c_id;');
        $this->addSql('ALTER TABLE track_e_downloads ADD CONSTRAINT FK_EEDF4DA6F004E599 FOREIGN KEY (resource_link_id) REFERENCES resource_link (id) ON DELETE SET NULL;');
        $this->addSql('CREATE INDEX IDX_EEDF4DA6F004E599 ON track_e_downloads (resource_link_id);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track_e_downloads DROP FOREIGN KEY FK_EEDF4DA6F004E599;');
        $this->addSql('DROP INDEX IDX_EEDF4DA6F004E599 ON track_e_downloads;');
        $this->addSql('ALTER TABLE track_e_downloads DROP resource_link_id;');
        $this->addSql('ALTER TABLE track_e_downloads ADD c_id INT NOT NULL;');
        $this->addSql('ALTER TABLE track_e_downloads ADD down_session_id INT DEFAULT NULL;');
        $this->addSql('CREATE INDEX idx_ted_c_id ON track_e_downloads (c_id);');
        $this->addSql('CREATE INDEX down_session_id ON track_e_downloads (down_session_id);');
    }
}
