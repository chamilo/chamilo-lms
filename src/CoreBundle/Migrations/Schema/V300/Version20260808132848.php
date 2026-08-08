<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Existing-platform users can be invited by email: the invitation is bound to
 * that user (invited_user_id) so only they can redeem the 7-day login-and-join
 * link. Registration invitations keep invited_user_id NULL.
 */
final class Version20260808132848 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add invited_user_id to course_invitation for existing-user email invitations';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('course_invitation')) {
            return;
        }

        $table = $schema->getTable('course_invitation');
        if ($table->hasColumn('invited_user_id')) {
            return;
        }

        $this->addSql('ALTER TABLE course_invitation ADD invited_user_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_course_invitation_invited_user ON course_invitation (invited_user_id)');
        $this->addSql('ALTER TABLE course_invitation ADD CONSTRAINT FK_COURSE_INVITATION_INVITED_USER FOREIGN KEY (invited_user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('course_invitation')) {
            return;
        }

        $table = $schema->getTable('course_invitation');
        if (!$table->hasColumn('invited_user_id')) {
            return;
        }

        $this->addSql('ALTER TABLE course_invitation DROP FOREIGN KEY FK_COURSE_INVITATION_INVITED_USER');
        $this->addSql('DROP INDEX idx_course_invitation_invited_user ON course_invitation');
        $this->addSql('ALTER TABLE course_invitation DROP invited_user_id');
    }
}
