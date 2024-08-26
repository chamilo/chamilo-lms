<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Schema\Schema;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;

final class Version20240811221800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration for creating new tables corresponding to the entities c_blog, c_blog_attachment, c_blog_comment, c_blog_post, c_blog_rating, c_blog_rel_user, c_blog_task, c_blog_task_rel_user, and block.';
    }

    public function up(Schema $schema): void
    {
        // Create block table
        if (!$schema->hasTable('block')) {
            $this->addSql('
                CREATE TABLE block (
                    id INT AUTO_INCREMENT NOT NULL,
                    title VARCHAR(255) DEFAULT NULL,
                    description LONGTEXT DEFAULT NULL,
                    path VARCHAR(190) NOT NULL,
                    controller VARCHAR(100) NOT NULL,
                    active TINYINT(1) NOT NULL,
                    user_id INT DEFAULT NULL,
                    PRIMARY KEY(id),
                    UNIQUE KEY path (path),
                    CONSTRAINT FK_831B9722A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }

        // Create c_blog table
        if (!$schema->hasTable('c_blog')) {
            $this->addSql('
                CREATE TABLE c_blog (
                    iid INT AUTO_INCREMENT NOT NULL,
                    resource_node_id INT DEFAULT NULL,
                    title LONGTEXT NOT NULL,
                    blog_subtitle VARCHAR(250) DEFAULT NULL,
                    date_creation DATETIME NOT NULL,
                    PRIMARY KEY(iid),
                    UNIQUE INDEX UNIQ_64B00A121BAD783F (resource_node_id),
                    CONSTRAINT FK_64B00A121BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }

        // Create c_blog_attachment table
        if (!$schema->hasTable('c_blog_attachment')) {
            $this->addSql('
                CREATE TABLE c_blog_attachment (
                    iid INT AUTO_INCREMENT NOT NULL,
                    path VARCHAR(255) NOT NULL,
                    comment LONGTEXT DEFAULT NULL,
                    size INT NOT NULL,
                    blog_id INT DEFAULT NULL,
                    filename VARCHAR(255) NOT NULL,
                    PRIMARY KEY(iid),
                    INDEX IDX_E769AADCDAE07E97 (blog_id),
                    CONSTRAINT FK_E769AADCDAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }

        // Create c_blog_comment table
        if (!$schema->hasTable('c_blog_comment')) {
            $this->addSql('
                CREATE TABLE c_blog_comment (
                    iid INT AUTO_INCREMENT NOT NULL,
                    title VARCHAR(250) NOT NULL,
                    comment LONGTEXT NOT NULL,
                    author_id INT DEFAULT NULL,
                    date_creation DATETIME NOT NULL,
                    blog_id INT DEFAULT NULL,
                    PRIMARY KEY(iid),
                    INDEX IDX_CAA18F1F675F31B (author_id),
                    INDEX IDX_CAA18F1DAE07E97 (blog_id),
                    CONSTRAINT FK_CAA18F1F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE,
                    CONSTRAINT FK_CAA18F1DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }

        // Create c_blog_post table
        if (!$schema->hasTable('c_blog_post')) {
            $this->addSql('
                CREATE TABLE c_blog_post (
                    iid INT AUTO_INCREMENT NOT NULL,
                    title VARCHAR(250) NOT NULL,
                    full_text LONGTEXT NOT NULL,
                    date_creation DATETIME NOT NULL,
                    author_id INT DEFAULT NULL,
                    blog_id INT DEFAULT NULL,
                    PRIMARY KEY(iid),
                    INDEX IDX_B6FD68A3F675F31B (author_id),
                    INDEX IDX_B6FD68A3DAE07E97 (blog_id),
                    CONSTRAINT FK_B6FD68A3F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE,
                    CONSTRAINT FK_B6FD68A3DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }

        // Create c_blog_rating table
        if (!$schema->hasTable('c_blog_rating')) {
            $this->addSql('
                CREATE TABLE c_blog_rating (
                    iid INT AUTO_INCREMENT NOT NULL,
                    rating_type VARCHAR(40) NOT NULL,
                    user_id INT DEFAULT NULL,
                    blog_id INT DEFAULT NULL,
                    rating INT NOT NULL,
                    PRIMARY KEY(iid),
                    INDEX IDX_D4E30760DAE07E97 (blog_id),
                    INDEX IDX_D4E30760A76ED395 (user_id),
                    CONSTRAINT FK_D4E30760DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE,
                    CONSTRAINT FK_D4E30760A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }

        // Create c_blog_rel_user table
        if (!$schema->hasTable('c_blog_rel_user')) {
            $this->addSql('
                CREATE TABLE c_blog_rel_user (
                    iid INT AUTO_INCREMENT NOT NULL,
                    user_id INT DEFAULT NULL,
                    blog_id INT DEFAULT NULL,
                    PRIMARY KEY(iid),
                    INDEX IDX_B55D851BDAE07E97 (blog_id),
                    INDEX IDX_B55D851BA76ED395 (user_id),
                    CONSTRAINT FK_B55D851BDAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE,
                    CONSTRAINT FK_B55D851BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }

        // Create c_blog_task table
        if (!$schema->hasTable('c_blog_task')) {
            $this->addSql('
                CREATE TABLE c_blog_task (
                    iid INT AUTO_INCREMENT NOT NULL,
                    title VARCHAR(250) NOT NULL,
                    description LONGTEXT NOT NULL,
                    color VARCHAR(10) NOT NULL,
                    blog_id INT DEFAULT NULL,
                    PRIMARY KEY(iid),
                    INDEX IDX_BE09DF0BDAE07E97 (blog_id),
                    CONSTRAINT FK_BE09DF0BDAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }

        // Create c_blog_task_rel_user table
        if (!$schema->hasTable('c_blog_task_rel_user')) {
            $this->addSql('
                CREATE TABLE c_blog_task_rel_user (
                    iid INT AUTO_INCREMENT NOT NULL,
                    user_id INT DEFAULT NULL,
                    blog_id INT DEFAULT NULL,
                    task_id INT DEFAULT NULL,
                    target_date DATE NOT NULL,
                    PRIMARY KEY(iid),
                    INDEX IDX_FD8B3C73DAE07E97 (blog_id),
                    INDEX IDX_FD8B3C738DB60186 (task_id),
                    INDEX IDX_FD8B3C73A76ED395 (user_id),
                    CONSTRAINT FK_FD8B3C73DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE,
                    CONSTRAINT FK_FD8B3C738DB60186 FOREIGN KEY (task_id) REFERENCES c_blog_task (iid) ON DELETE CASCADE,
                    CONSTRAINT FK_FD8B3C73A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS block;');
        $this->addSql('DROP TABLE IF EXISTS c_blog_attachment;');
        $this->addSql('DROP TABLE IF EXISTS c_blog_comment;');
        $this->addSql('DROP TABLE IF EXISTS c_blog_post;');
        $this->addSql('DROP TABLE IF EXISTS c_blog_rating;');
        $this->addSql('DROP TABLE IF EXISTS c_blog_rel_user;');
        $this->addSql('DROP TABLE IF EXISTS c_blog_task_rel_user;');
        $this->addSql('DROP TABLE IF EXISTS c_blog_task;');
        $this->addSql('DROP TABLE IF EXISTS c_blog;');
    }
}
