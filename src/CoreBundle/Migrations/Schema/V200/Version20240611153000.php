<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240611153000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update collation for specific columns in c_announcement, message, and notification tables to utf8mb4';
    }

    public function up(Schema $schema): void
    {
        // Change collation for c_announcement table
        $this->addSql('ALTER TABLE c_announcement CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_announcement CHANGE title title TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_announcement CHANGE content content LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');

        // Change collation for message table
        $this->addSql('ALTER TABLE message CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE message CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE message CHANGE content content LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');

        // Change collation for notification table
        $this->addSql('ALTER TABLE notification CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE notification CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE notification CHANGE content content LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');

        // Change collation for dest_mail column in notification table
        $this->addSql('ALTER TABLE notification CHANGE dest_mail dest_mail VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert collation for c_announcement table
        $this->addSql('ALTER TABLE c_announcement CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_announcement CHANGE title title TEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_announcement CHANGE content content LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');

        // Revert collation for message table
        $this->addSql('ALTER TABLE message CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE message CHANGE title title VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE message CHANGE content content LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');

        // Revert collation for notification table
        $this->addSql('ALTER TABLE notification CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE notification CHANGE title title VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE notification CHANGE content content LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');

        // Revert collation for dest_mail column in notification table
        $this->addSql('ALTER TABLE notification CHANGE dest_mail dest_mail VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL');
    }
}
