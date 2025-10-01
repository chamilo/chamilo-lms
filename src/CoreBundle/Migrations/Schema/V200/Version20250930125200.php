<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250930125200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Blog tasks & relations: author_id on c_blog_task, status on rel table, unique indexes, harden NOT NULLs, enforce rating/post FKs.';
    }

    public function up(Schema $schema): void
    {
        /* =========================
         * c_blog_task
         * ========================= */
        // Add author_id if missing
        $this->addSql('ALTER TABLE c_blog_task ADD COLUMN IF NOT EXISTS author_id INT DEFAULT NULL');

        // Normalize defaults/constraints
        $this->addSql("ALTER TABLE c_blog_task MODIFY blog_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_blog_task MODIFY task_id INT NOT NULL DEFAULT 0");
        $this->addSql("ALTER TABLE c_blog_task MODIFY description LONGTEXT NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE c_blog_task MODIFY color VARCHAR(10) NOT NULL DEFAULT '#0ea5e9'");
        $this->addSql("ALTER TABLE c_blog_task MODIFY system_task TINYINT(1) NOT NULL DEFAULT 0");

        // FK + index for author_id
        $this->addSql('DROP INDEX IF EXISTS IDX_BE09DF0BF675F31B ON c_blog_task');
        $this->addSql('CREATE INDEX IDX_BE09DF0BF675F31B ON c_blog_task (author_id)');
        $this->addSql('ALTER TABLE c_blog_task DROP FOREIGN KEY IF EXISTS FK_BE09DF0BF675F31B');
        $this->addSql('ALTER TABLE c_blog_task ADD CONSTRAINT FK_BE09DF0BF675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL');

        /* =========================
         * c_blog_task_rel_user
         * ========================= */
        // Add status if missing
        $this->addSql('ALTER TABLE c_blog_task_rel_user ADD COLUMN IF NOT EXISTS status SMALLINT NOT NULL DEFAULT 0');

        // Harden NOT NULLs
        $this->addSql('ALTER TABLE c_blog_task_rel_user MODIFY blog_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_blog_task_rel_user MODIFY user_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_blog_task_rel_user MODIFY task_id INT NOT NULL');

        // Deduplicate to safely create unique index
        $this->addSql('
            DELETE t1 FROM c_blog_task_rel_user t1
            INNER JOIN c_blog_task_rel_user t2
              ON t1.task_id = t2.task_id
             AND t1.user_id = t2.user_id
             AND t1.blog_id = t2.blog_id
             AND t1.target_date = t2.target_date
             AND t1.iid > t2.iid
        ');
        $this->addSql('DROP INDEX IF EXISTS uniq_task_user_blog_date ON c_blog_task_rel_user');
        $this->addSql('CREATE UNIQUE INDEX uniq_task_user_blog_date ON c_blog_task_rel_user (task_id, user_id, blog_id, target_date)');

        /* =========================
         * c_blog_rel_user
         * ========================= */
        // Harden NOT NULLs
        $this->addSql('ALTER TABLE c_blog_rel_user MODIFY blog_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_blog_rel_user MODIFY user_id INT NOT NULL');

        // Deduplicate then unique index
        $this->addSql('
            DELETE r1 FROM c_blog_rel_user r1
            INNER JOIN c_blog_rel_user r2
              ON r1.blog_id = r2.blog_id
             AND r1.user_id = r2.user_id
             AND r1.iid > r2.iid
        ');
        $this->addSql('DROP INDEX IF EXISTS uniq_blog_user ON c_blog_rel_user');
        $this->addSql('CREATE UNIQUE INDEX uniq_blog_user ON c_blog_rel_user (blog_id, user_id)');

        /* =========================
         * c_blog_comment (post_id NOT NULL)
         * ========================= */
        $this->addSql('UPDATE c_blog_comment SET post_id = NULL WHERE post_id = 0');
        $this->addSql('
            UPDATE c_blog_comment c
            LEFT JOIN c_blog_post p ON c.post_id = p.iid
            SET c.post_id = NULL
            WHERE c.post_id IS NOT NULL AND p.iid IS NULL
        ');
        $this->addSql('DELETE FROM c_blog_comment WHERE post_id IS NULL');

        $this->addSql('ALTER TABLE c_blog_comment MODIFY post_id INT NOT NULL');

        /* =========================
         * c_blog_rating (post_id NOT NULL + FK a post)
         * ========================= */
        $this->addSql('UPDATE c_blog_rating SET post_id = NULL WHERE post_id = 0');
        $this->addSql('
            UPDATE c_blog_rating r
            LEFT JOIN c_blog_post p ON r.post_id = p.iid
            SET r.post_id = NULL
            WHERE r.post_id IS NOT NULL AND p.iid IS NULL
        ');
        $this->addSql('DELETE FROM c_blog_rating WHERE post_id IS NULL');

        // NOT NULL + FK
        $this->addSql('ALTER TABLE c_blog_rating MODIFY post_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_blog_rating DROP FOREIGN KEY IF EXISTS FK_D4E307604B89032C');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_D4E307604B89032C ON c_blog_rating (post_id)');
        $this->addSql('ALTER TABLE c_blog_rating ADD CONSTRAINT FK_D4E307604B89032C FOREIGN KEY (post_id) REFERENCES c_blog_post (iid) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        /* c_blog_rating */
        $this->addSql('ALTER TABLE c_blog_rating DROP FOREIGN KEY IF EXISTS FK_D4E307604B89032C');
        $this->addSql('DROP INDEX IF EXISTS IDX_D4E307604B89032C ON c_blog_rating');
        // Allow NULL again for safe rollback
        $this->addSql('ALTER TABLE c_blog_rating MODIFY post_id INT NULL');

        /* c_blog_comment */
        // Allow NULL again for rollback
        $this->addSql('ALTER TABLE c_blog_comment MODIFY post_id INT NULL');

        /* c_blog_rel_user */
        $this->addSql('DROP INDEX IF EXISTS uniq_blog_user ON c_blog_rel_user');

        /* c_blog_task_rel_user */
        $this->addSql('DROP INDEX IF EXISTS uniq_task_user_blog_date ON c_blog_task_rel_user');
        $this->addSql('ALTER TABLE c_blog_task_rel_user DROP COLUMN IF EXISTS status');

        /* c_blog_task */
        $this->addSql('ALTER TABLE c_blog_task DROP FOREIGN KEY IF EXISTS FK_BE09DF0BF675F31B');
        $this->addSql('DROP INDEX IF EXISTS IDX_BE09DF0BF675F31B ON c_blog_task');
        $this->addSql('ALTER TABLE c_blog_task DROP COLUMN IF EXISTS author_id');
    }
}
