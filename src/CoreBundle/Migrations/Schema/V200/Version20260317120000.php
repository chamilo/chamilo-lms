<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260317120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add indexes on session table for list filtering performance (access_end_date, status, parent_id, session_category_id).';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('session');

        if (!$table->hasIndex('idx_session_access_end_date')) {
            $this->addSql('CREATE INDEX idx_session_access_end_date ON session (access_end_date)');
        }

        if (!$table->hasIndex('idx_session_status')) {
            $this->addSql('CREATE INDEX idx_session_status ON session (status)');
        }

        if (!$table->hasIndex('idx_session_parent_id')) {
            $this->addSql('CREATE INDEX idx_session_parent_id ON session (parent_id)');
        }

        if (!$table->hasIndex('idx_session_category_id')) {
            $this->addSql('CREATE INDEX idx_session_category_id ON session (session_category_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('session');

        if ($table->hasIndex('idx_session_access_end_date')) {
            $this->addSql('DROP INDEX idx_session_access_end_date ON session');
        }

        if ($table->hasIndex('idx_session_status')) {
            $this->addSql('DROP INDEX idx_session_status ON session');
        }

        if ($table->hasIndex('idx_session_parent_id')) {
            $this->addSql('DROP INDEX idx_session_parent_id ON session');
        }

        if ($table->hasIndex('idx_session_category_id')) {
            $this->addSql('DROP INDEX idx_session_category_id ON session');
        }
    }
}
