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
        return 'Add indexes on session and session_rel_user tables for list filtering performance.';
    }

    public function up(Schema $schema): void
    {
        $session = $schema->getTable('session');

        if (!$session->hasIndex('idx_session_access_end_date')) {
            $this->addSql('CREATE INDEX idx_session_access_end_date ON session (access_end_date)');
        }

        if (!$session->hasIndex('idx_session_status')) {
            $this->addSql('CREATE INDEX idx_session_status ON session (status)');
        }

        if (!$session->hasIndex('idx_session_parent_id')) {
            $this->addSql('CREATE INDEX idx_session_parent_id ON session (parent_id)');
        }

        if (!$session->hasIndex('idx_session_category_id')) {
            $this->addSql('CREATE INDEX idx_session_category_id ON session (session_category_id)');
        }

        $sru = $schema->getTable('session_rel_user');

        if (!$sru->hasIndex('idx_session_rel_user_user_reltype_session')) {
            $this->addSql('CREATE INDEX idx_session_rel_user_user_reltype_session ON session_rel_user (user_id, relation_type, session_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $session = $schema->getTable('session');

        if ($session->hasIndex('idx_session_access_end_date')) {
            $this->addSql('DROP INDEX idx_session_access_end_date ON session');
        }

        if ($session->hasIndex('idx_session_status')) {
            $this->addSql('DROP INDEX idx_session_status ON session');
        }

        if ($session->hasIndex('idx_session_parent_id')) {
            $this->addSql('DROP INDEX idx_session_parent_id ON session');
        }

        if ($session->hasIndex('idx_session_category_id')) {
            $this->addSql('DROP INDEX idx_session_category_id ON session');
        }

        $sru = $schema->getTable('session_rel_user');

        if ($sru->hasIndex('idx_session_rel_user_user_reltype_session')) {
            $this->addSql('DROP INDEX idx_session_rel_user_user_reltype_session ON session_rel_user');
        }
    }
}
