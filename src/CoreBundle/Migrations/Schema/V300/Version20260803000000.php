<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds the course_invitation table: a pending or accepted invitation sent to
 * an email address that may not have a Chamilo account yet, letting that
 * person register and get auto-subscribed to a course or a whole session in
 * one step. Referenced by validation_token via that token's resource_id
 * (new TYPE_COURSE_INVITATION type), which carries the one-time secret hash.
 * Pinned to the AccessUrl it was issued from, so the same link cannot be
 * redeemed from a different portal on a multi-URL install. A pending
 * invitation may also be revoked by whoever sent it (revoked_at).
 */
final class Version20260803000000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add the course_invitation table for course/session registration invitations';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('course_invitation')) {
            $this->addSql(<<<'SQL'
            CREATE TABLE course_invitation (
              id INT AUTO_INCREMENT NOT NULL,
              email VARCHAR(255) NOT NULL,
              c_id INT DEFAULT NULL,
              session_id INT DEFAULT NULL,
              exercise_id INT DEFAULT NULL,
              access_url_id INT NOT NULL,
              created_by INT NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              accepted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              registered_user_id INT DEFAULT NULL,
              INDEX idx_course_invitation_email (email),
              INDEX idx_course_invitation_course (c_id),
              INDEX idx_course_invitation_session (session_id),
              INDEX idx_course_invitation_access_url (access_url_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
            SQL);

            $this->addSql('ALTER TABLE course_invitation ADD CONSTRAINT FK_COURSE_INVITATION_COURSE FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE course_invitation ADD CONSTRAINT FK_COURSE_INVITATION_SESSION FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE course_invitation ADD CONSTRAINT FK_COURSE_INVITATION_ACCESS_URL FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE course_invitation ADD CONSTRAINT FK_COURSE_INVITATION_CREATED_BY FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE course_invitation ADD CONSTRAINT FK_COURSE_INVITATION_REGISTERED_USER FOREIGN KEY (registered_user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('course_invitation')) {
            $this->addSql('DROP TABLE course_invitation');
        }
    }
}
