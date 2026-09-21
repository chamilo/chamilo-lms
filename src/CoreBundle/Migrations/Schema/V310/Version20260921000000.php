<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Reorders course_rel_user's two composite indexes so their filter columns
 * lead instead of the (already unique) primary key `id`. As created in
 * V200\Version20191101132000, both indexes had `id` first, which made them
 * unusable as an index seek for the common "list users of this course"
 * query (WHERE c_id = ? / WHERE user_id = ?): a composite index can only be
 * used from its leftmost column, and `id` is never the predicate there.
 */
final class Version20260921000000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Reorder course_rel_user indexes to lead with c_id/user_id instead of id';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('course_rel_user')) {
            return;
        }

        $table = $schema->getTable('course_rel_user');

        if ($table->hasIndex('course_rel_user_user_id')) {
            $this->addSql('DROP INDEX course_rel_user_user_id ON course_rel_user');
        }
        $this->addSql('CREATE INDEX course_rel_user_user_id ON course_rel_user (user_id, id)');

        if ($table->hasIndex('course_rel_user_c_id_user_id')) {
            $this->addSql('DROP INDEX course_rel_user_c_id_user_id ON course_rel_user');
        }
        $this->addSql('CREATE INDEX course_rel_user_c_id_user_id ON course_rel_user (c_id, user_id, id)');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('course_rel_user')) {
            return;
        }

        $table = $schema->getTable('course_rel_user');

        if ($table->hasIndex('course_rel_user_user_id')) {
            $this->addSql('DROP INDEX course_rel_user_user_id ON course_rel_user');
        }
        $this->addSql('CREATE INDEX course_rel_user_user_id ON course_rel_user (id, user_id)');

        if ($table->hasIndex('course_rel_user_c_id_user_id')) {
            $this->addSql('DROP INDEX course_rel_user_c_id_user_id ON course_rel_user');
        }
        $this->addSql('CREATE INDEX course_rel_user_c_id_user_id ON course_rel_user (id, c_id, user_id)');
    }
}
