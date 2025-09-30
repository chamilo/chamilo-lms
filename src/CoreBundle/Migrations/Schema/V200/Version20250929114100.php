<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250929114100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'BlogAttachment: add nullable post_id and comment_id with FKs. Update c_blog_comment (post_id, parent_comment_id, legacy default). Add c_blog_rating.post_id with FK. Includes data cleanup to avoid FK failures.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_blog_attachment ADD COLUMN IF NOT EXISTS post_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_blog_attachment ADD COLUMN IF NOT EXISTS comment_id INT DEFAULT NULL');

        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_E769AADC4B89032C ON c_blog_attachment (post_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_E769AADCF8697D13 ON c_blog_attachment (comment_id)');

        $this->addSql('ALTER TABLE c_blog_attachment DROP FOREIGN KEY IF EXISTS FK_E769AADC4B89032C');
        $this->addSql('ALTER TABLE c_blog_attachment DROP FOREIGN KEY IF EXISTS FK_E769AADCF8697D13');

        $this->addSql('ALTER TABLE c_blog_attachment ADD CONSTRAINT FK_E769AADC4B89032C FOREIGN KEY (post_id) REFERENCES c_blog_post (iid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE c_blog_attachment ADD CONSTRAINT FK_E769AADCF8697D13 FOREIGN KEY (comment_id) REFERENCES c_blog_comment (iid) ON DELETE CASCADE');


        // Ensure columns exist
        $this->addSql('ALTER TABLE c_blog_comment ADD COLUMN IF NOT EXISTS post_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_blog_comment ADD COLUMN IF NOT EXISTS parent_comment_id INT DEFAULT NULL');

        // Make them NULLABLE (some previous attempts may have left them NOT NULL)
        $this->addSql('ALTER TABLE c_blog_comment MODIFY post_id INT NULL');
        $this->addSql('ALTER TABLE c_blog_comment MODIFY parent_comment_id INT NULL');
        $this->addSql('ALTER TABLE c_blog_comment MODIFY comment_id INT NOT NULL DEFAULT 0');

        // DATA CLEANUP
        $this->addSql('UPDATE c_blog_comment SET post_id = NULL WHERE post_id = 0');
        $this->addSql('UPDATE c_blog_comment SET parent_comment_id = NULL WHERE parent_comment_id = 0');

        $this->addSql('
            UPDATE c_blog_comment c
            LEFT JOIN c_blog_post p ON c.post_id = p.iid
            SET c.post_id = NULL
            WHERE c.post_id IS NOT NULL AND p.iid IS NULL
        ');

        $this->addSql('
            UPDATE c_blog_comment c
            LEFT JOIN c_blog_comment pc ON c.parent_comment_id = pc.iid
            SET c.parent_comment_id = NULL
            WHERE c.parent_comment_id IS NOT NULL AND pc.iid IS NULL
        ');

        // Indexes
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_CAA18F14B89032C ON c_blog_comment (post_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_CAA18F1BF2AF943 ON c_blog_comment (parent_comment_id)');

        // FKs
        $this->addSql('ALTER TABLE c_blog_comment DROP FOREIGN KEY IF EXISTS FK_CAA18F14B89032C');
        $this->addSql('ALTER TABLE c_blog_comment DROP FOREIGN KEY IF EXISTS FK_CAA18F1BF2AF943');

        $this->addSql('ALTER TABLE c_blog_comment ADD CONSTRAINT FK_CAA18F14B89032C FOREIGN KEY (post_id) REFERENCES c_blog_post (iid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE c_blog_comment ADD CONSTRAINT FK_CAA18F1BF2AF943 FOREIGN KEY (parent_comment_id) REFERENCES c_blog_comment (iid) ON DELETE CASCADE');

        // Ensure column exists
        $this->addSql('ALTER TABLE c_blog_rating ADD COLUMN IF NOT EXISTS post_id INT DEFAULT NULL');

        // Make it NULLABLE in case it exists as NOT NULL
        $this->addSql('ALTER TABLE c_blog_rating MODIFY post_id INT NULL');

        // Cleanup
        $this->addSql('UPDATE c_blog_rating SET post_id = NULL WHERE post_id = 0');
        $this->addSql('
            UPDATE c_blog_rating r
            LEFT JOIN c_blog_post p ON r.post_id = p.iid
            SET r.post_id = NULL
            WHERE r.post_id IS NOT NULL AND p.iid IS NULL
        ');

        // Index + FK
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_D4E307604B89032C ON c_blog_rating (post_id)');
        $this->addSql('ALTER TABLE c_blog_rating DROP FOREIGN KEY IF EXISTS FK_D4E307604B89032C');
        $this->addSql('ALTER TABLE c_blog_rating ADD CONSTRAINT FK_D4E307604B89032C FOREIGN KEY (post_id) REFERENCES c_blog_post (iid) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_blog_rating DROP FOREIGN KEY IF EXISTS FK_D4E307604B89032C');
        $this->addSql('DROP INDEX IF NOT EXISTS IDX_D4E307604B89032C ON c_blog_rating');
        $this->addSql('ALTER TABLE c_blog_rating DROP COLUMN IF EXISTS post_id');

        $this->addSql('ALTER TABLE c_blog_comment DROP FOREIGN KEY IF EXISTS FK_CAA18F14B89032C');
        $this->addSql('ALTER TABLE c_blog_comment DROP FOREIGN KEY IF EXISTS FK_CAA18F1BF2AF943');

        $this->addSql('DROP INDEX IF NOT EXISTS IDX_CAA18F14B89032C ON c_blog_comment');
        $this->addSql('DROP INDEX IF NOT EXISTS IDX_CAA18F1BF2AF943 ON c_blog_comment');

        $this->addSql('ALTER TABLE c_blog_comment DROP COLUMN IF EXISTS post_id');
        $this->addSql('ALTER TABLE c_blog_comment DROP COLUMN IF EXISTS parent_comment_id');

        $this->addSql('ALTER TABLE c_blog_comment MODIFY comment_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE c_blog_attachment DROP FOREIGN KEY IF EXISTS FK_E769AADC4B89032C');
        $this->addSql('ALTER TABLE c_blog_attachment DROP FOREIGN KEY IF EXISTS FK_E769AADCF8697D13');

        $this->addSql('DROP INDEX IF NOT EXISTS IDX_E769AADC4B89032C ON c_blog_attachment');
        $this->addSql('DROP INDEX IF NOT EXISTS IDX_E769AADCF8697D13 ON c_blog_attachment');

        $this->addSql('ALTER TABLE c_blog_attachment DROP COLUMN IF EXISTS post_id');
        $this->addSql('ALTER TABLE c_blog_attachment DROP COLUMN IF EXISTS comment_id');
    }
}
