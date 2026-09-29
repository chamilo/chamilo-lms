<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240811221900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration for modifying existing tables: block, c_blog, c_blog_attachment, c_blog_comment, c_blog_post, c_blog_rating, c_blog_rel_user, c_blog_task, and c_blog_task_rel_user.';
    }

    public function up(Schema $schema): void
    {
        $blockTable = $schema->getTable('block');
        if (!$blockTable->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE block ADD user_id INT DEFAULT NULL;');
        }
        if (!$blockTable->hasForeignKey('FK_831B9722A76ED395')) {
            $this->addSql('ALTER TABLE block ADD CONSTRAINT FK_831B9722A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        }
        if (!$blockTable->hasIndex('UNIQ_831B9722A76ED395')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_831B9722A76ED395 ON block (user_id);');
        }

        $blogAttachmentTable = $schema->getTable('c_blog_attachment');
        if ($blogAttachmentTable->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_blog_attachment;');
        }
        if ($blogAttachmentTable->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_blog_attachment DROP id, DROP c_id, DROP post_id, DROP comment_id, CHANGE blog_id blog_id INT DEFAULT NULL;');
        }
        $this->addSql('DELETE FROM c_blog_attachment WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        if (!$blogAttachmentTable->hasForeignKey('FK_E769AADCDAE07E97')) {
            $this->addSql('ALTER TABLE c_blog_attachment ADD CONSTRAINT FK_E769AADCDAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        }
        if (!$blogAttachmentTable->hasIndex('IDX_E769AADCDAE07E97')) {
            $this->addSql('CREATE INDEX IDX_E769AADCDAE07E97 ON c_blog_attachment (blog_id);');
        }

        $blogRatingTable = $schema->getTable('c_blog_rating');
        if ($blogRatingTable->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_blog_rating;');
        }
        if ($blogRatingTable->hasColumn('rating_id')) {
            $this->addSql('ALTER TABLE c_blog_rating DROP rating_id, DROP c_id, DROP item_id, CHANGE blog_id blog_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL;');
        }
        $this->addSql('DELETE FROM c_blog_rating WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        if (!$blogRatingTable->hasForeignKey('FK_D4E30760DAE07E97')) {
            $this->addSql('ALTER TABLE c_blog_rating ADD CONSTRAINT FK_D4E30760DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        }
        $this->addSql('DELETE FROM c_blog_rating WHERE user_id NOT IN (SELECT id FROM user);');
        if (!$blogRatingTable->hasForeignKey('FK_D4E30760A76ED395')) {
            $this->addSql('ALTER TABLE c_blog_rating ADD CONSTRAINT FK_D4E30760A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        }
        if (!$blogRatingTable->hasIndex('IDX_D4E30760DAE07E97')) {
            $this->addSql('CREATE INDEX IDX_D4E30760DAE07E97 ON c_blog_rating (blog_id);');
        }
        if (!$blogRatingTable->hasIndex('IDX_D4E30760A76ED395')) {
            $this->addSql('CREATE INDEX IDX_D4E30760A76ED395 ON c_blog_rating (user_id);');
        }

        $blogPostTable = $schema->getTable('c_blog_post');
        if ($blogPostTable->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_blog_post;');
        }
        if ($blogPostTable->hasColumn('post_id')) {
            $this->addSql('ALTER TABLE c_blog_post DROP c_id, DROP post_id, CHANGE blog_id blog_id INT DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL;');
        }
        $this->addSql('DELETE FROM c_blog_post WHERE author_id NOT IN (SELECT id FROM user);');
        if (!$blogPostTable->hasForeignKey('FK_B6FD68A3F675F31B')) {
            $this->addSql('ALTER TABLE c_blog_post ADD CONSTRAINT FK_B6FD68A3F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE;');
        }
        $this->addSql('DELETE FROM c_blog_post WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        if (!$blogPostTable->hasForeignKey('FK_B6FD68A3DAE07E97')) {
            $this->addSql('ALTER TABLE c_blog_post ADD CONSTRAINT FK_B6FD68A3DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        }
        if (!$blogPostTable->hasIndex('IDX_B6FD68A3F675F31B')) {
            $this->addSql('CREATE INDEX IDX_B6FD68A3F675F31B ON c_blog_post (author_id);');
        }
        if (!$blogPostTable->hasIndex('IDX_B6FD68A3DAE07E97')) {
            $this->addSql('CREATE INDEX IDX_B6FD68A3DAE07E97 ON c_blog_post (blog_id);');
        }

        $blogRelUserTable = $schema->getTable('c_blog_rel_user');
        if ($blogRelUserTable->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_blog_rel_user;');
        }
        if ($blogRelUserTable->hasColumn('c_id')) {
            $this->addSql('ALTER TABLE c_blog_rel_user DROP c_id, CHANGE blog_id blog_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL;');
        }
        $this->addSql('DELETE FROM c_blog_rel_user WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        if (!$blogRelUserTable->hasForeignKey('FK_B55D851BDAE07E97')) {
            $this->addSql('ALTER TABLE c_blog_rel_user ADD CONSTRAINT FK_B55D851BDAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        }
        $this->addSql('DELETE FROM c_blog_rel_user WHERE user_id NOT IN (SELECT id FROM user);');
        if (!$blogRelUserTable->hasForeignKey('FK_B55D851BA76ED395')) {
            $this->addSql('ALTER TABLE c_blog_rel_user ADD CONSTRAINT FK_B55D851BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        }
        if (!$blogRelUserTable->hasIndex('IDX_B55D851BDAE07E97')) {
            $this->addSql('CREATE INDEX IDX_B55D851BDAE07E97 ON c_blog_rel_user (blog_id);');
        }
        if (!$blogRelUserTable->hasIndex('IDX_B55D851BA76ED395')) {
            $this->addSql('CREATE INDEX IDX_B55D851BA76ED395 ON c_blog_rel_user (user_id);');
        }

        $blogTaskRelUserTable = $schema->getTable('c_blog_task_rel_user');
        if ($blogTaskRelUserTable->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_blog_task_rel_user;');
        }
        if ($blogTaskRelUserTable->hasIndex('user')) {
            $this->addSql('DROP INDEX user ON c_blog_task_rel_user;');
        }
        if ($blogTaskRelUserTable->hasIndex('task')) {
            $this->addSql('DROP INDEX task ON c_blog_task_rel_user;');
        }
        if ($blogTaskRelUserTable->hasColumn('c_id')) {
            $this->addSql('ALTER TABLE c_blog_task_rel_user DROP c_id, CHANGE blog_id blog_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE task_id task_id INT DEFAULT NULL;');
        }
        $this->addSql('DELETE FROM c_blog_task_rel_user WHERE task_id NOT IN (SELECT iid FROM c_blog_task);');
        if (!$blogTaskRelUserTable->hasForeignKey('FK_FD8B3C738DB60186')) {
            $this->addSql('ALTER TABLE c_blog_task_rel_user ADD CONSTRAINT FK_FD8B3C738DB60186 FOREIGN KEY (task_id) REFERENCES c_blog_task (iid) ON DELETE CASCADE;');
        }
        $this->addSql('DELETE FROM c_blog_task_rel_user WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        if (!$blogTaskRelUserTable->hasForeignKey('FK_FD8B3C73DAE07E97')) {
            $this->addSql('ALTER TABLE c_blog_task_rel_user ADD CONSTRAINT FK_FD8B3C73DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        }
        $this->addSql('DELETE FROM c_blog_task_rel_user WHERE user_id NOT IN (SELECT id FROM user);');
        if (!$blogTaskRelUserTable->hasForeignKey('FK_FD8B3C73A76ED395')) {
            $this->addSql('ALTER TABLE c_blog_task_rel_user ADD CONSTRAINT FK_FD8B3C73A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        }
        if (!$blogTaskRelUserTable->hasIndex('IDX_FD8B3C73DAE07E97')) {
            $this->addSql('CREATE INDEX IDX_FD8B3C73DAE07E97 ON c_blog_task_rel_user (blog_id);');
        }
        if (!$blogTaskRelUserTable->hasIndex('IDX_FD8B3C738DB60186')) {
            $this->addSql('CREATE INDEX IDX_FD8B3C738DB60186 ON c_blog_task_rel_user (task_id);');
        }
        if (!$blogTaskRelUserTable->hasIndex('IDX_FD8B3C73A76ED395')) {
            $this->addSql('CREATE INDEX IDX_FD8B3C73A76ED395 ON c_blog_task_rel_user (user_id);');
        }

        $blogTable = $schema->getTable('c_blog');
        if ($blogTable->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_blog;');
        }
        if ($blogTable->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON c_blog;');
        }
        if ($blogTable->hasColumn('visibility')) {
            $this->addSql('ALTER TABLE c_blog DROP c_id, DROP blog_id, DROP visibility, DROP session_id;');
        }
        if (!$blogTable->hasForeignKey('FK_64B00A121BAD783F')) {
            $this->addSql('ALTER TABLE c_blog ADD CONSTRAINT FK_64B00A121BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;');
        }

        if ($schema->getTable('c_lp_category_rel_user')->hasForeignKey('FK_61F0427A76ED395')) {
            $this->addSql('ALTER TABLE c_lp_category_rel_user DROP FOREIGN KEY FK_61F0427A76ED395;');
        }

        $blogTaskTable = $schema->getTable('c_blog_task');
        if ($blogTaskTable->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_blog_task;');
        }
        if ($blogTaskTable->hasColumn('c_id')) {
            $this->addSql('ALTER TABLE c_blog_task DROP c_id, CHANGE blog_id blog_id INT DEFAULT NULL;');
        }
        $this->addSql('DELETE FROM c_blog_task WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        if (!$blogTaskTable->hasForeignKey('FK_BE09DF0BDAE07E97')) {
            $this->addSql('ALTER TABLE c_blog_task ADD CONSTRAINT FK_BE09DF0BDAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        }
        if (!$blogTaskTable->hasIndex('IDX_BE09DF0BDAE07E97')) {
            $this->addSql('CREATE INDEX IDX_BE09DF0BDAE07E97 ON c_blog_task (blog_id);');
        }

        $blogCommentTable = $schema->getTable('c_blog_comment');
        if ($blogCommentTable->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_blog_comment;');
        }
        if ($blogCommentTable->hasColumn('post_id')) {
            $this->addSql('ALTER TABLE c_blog_comment DROP c_id, DROP post_id, DROP task_id, DROP parent_comment_id, CHANGE author_id author_id INT DEFAULT NULL, CHANGE blog_id blog_id INT DEFAULT NULL;');
        }
        $this->addSql('DELETE FROM c_blog_comment WHERE author_id NOT IN (SELECT id FROM user);');
        if (!$blogCommentTable->hasForeignKey('FK_CAA18F1F675F31B')) {
            $this->addSql('ALTER TABLE c_blog_comment ADD CONSTRAINT FK_CAA18F1F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE;');
        }
        $this->addSql('DELETE FROM c_blog_comment WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        if (!$blogCommentTable->hasForeignKey('FK_CAA18F1DAE07E97')) {
            $this->addSql('ALTER TABLE c_blog_comment ADD CONSTRAINT FK_CAA18F1DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        }
        if (!$blogCommentTable->hasIndex('IDX_CAA18F1F675F31B')) {
            $this->addSql('CREATE INDEX IDX_CAA18F1F675F31B ON c_blog_comment (author_id);');
        }
        if (!$blogCommentTable->hasIndex('IDX_CAA18F1DAE07E97')) {
            $this->addSql('CREATE INDEX IDX_CAA18F1DAE07E97 ON c_blog_comment (blog_id);');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE block DROP FOREIGN KEY FK_831B9722A76ED395;');
        $this->addSql('ALTER TABLE block DROP COLUMN user_id;');
        $this->addSql('DROP INDEX UNIQ_831B9722A76ED395 ON block;');

        $this->addSql('ALTER TABLE c_blog_attachment DROP FOREIGN KEY FK_E769AADCDAE07E97;');
        $this->addSql('ALTER TABLE c_blog_attachment ADD c_id INT NOT NULL, ADD post_id INT NOT NULL, ADD comment_id INT NOT NULL;');
        $this->addSql('ALTER TABLE c_blog_attachment ADD id INT NOT NULL;');
        $this->addSql('DROP INDEX IDX_E769AADCDAE07E97 ON c_blog_attachment;');
        $this->addSql('CREATE INDEX course ON c_blog_attachment (c_id);');

        $this->addSql('ALTER TABLE c_blog_rating DROP FOREIGN KEY FK_D4E30760DAE07E97;');
        $this->addSql('ALTER TABLE c_blog_rating DROP FOREIGN KEY FK_D4E30760A76ED395;');
        $this->addSql('ALTER TABLE c_blog_rating ADD c_id INT NOT NULL, ADD rating_id INT NOT NULL, ADD item_id INT NOT NULL;');
        $this->addSql('DROP INDEX IDX_D4E30760DAE07E97 ON c_blog_rating;');
        $this->addSql('DROP INDEX IDX_D4E30760A76ED395 ON c_blog_rating;');
        $this->addSql('CREATE INDEX course ON c_blog_rating (c_id);');

        $this->addSql('ALTER TABLE c_survey_question_option DROP FOREIGN KEY FK_C4B6F5F1E27F6BF;');
        $this->addSql('ALTER TABLE c_survey_question_option ADD CONSTRAINT FK_C4B6F5F1E27F6BF FOREIGN KEY (question_id) REFERENCES c_survey_question (iid);');

        $this->addSql('ALTER TABLE c_blog_post DROP FOREIGN KEY FK_B6FD68A3F675F31B;');
        $this->addSql('ALTER TABLE c_blog_post DROP FOREIGN KEY FK_B6FD68A3DAE07E97;');
        $this->addSql('ALTER TABLE c_blog_post ADD c_id INT NOT NULL, ADD post_id INT NOT NULL;');
        $this->addSql('DROP INDEX IDX_B6FD68A3F675F31B ON c_blog_post;');
        $this->addSql('DROP INDEX IDX_B6FD68A3DAE07E97 ON c_blog_post;');
        $this->addSql('CREATE INDEX course ON c_blog_post (c_id);');

        $this->addSql('ALTER TABLE c_blog_rel_user DROP FOREIGN KEY FK_B55D851BDAE07E97;');
        $this->addSql('ALTER TABLE c_blog_rel_user DROP FOREIGN KEY FK_B55D851BA76ED395;');
        $this->addSql('ALTER TABLE c_blog_rel_user ADD c_id INT NOT NULL;');
        $this->addSql('DROP INDEX IDX_B55D851BDAE07E97 ON c_blog_rel_user;');
        $this->addSql('DROP INDEX IDX_B55D851BA76ED395 ON c_blog_rel_user;');
        $this->addSql('CREATE INDEX course ON c_blog_rel_user (c_id);');

        $this->addSql('ALTER TABLE c_blog_task_rel_user DROP FOREIGN KEY FK_FD8B3C738DB60186;');
        $this->addSql('ALTER TABLE c_blog_task_rel_user DROP FOREIGN KEY FK_FD8B3C73DAE07E97;');
        $this->addSql('ALTER TABLE c_blog_task_rel_user DROP FOREIGN KEY FK_FD8B3C73A76ED395;');
        $this->addSql('ALTER TABLE c_blog_task_rel_user ADD c_id INT NOT NULL;');
        $this->addSql('ALTER TABLE c_blog_task_rel_user ADD task_id INT NOT NULL, ADD user_id INT NOT NULL;');
        $this->addSql('DROP INDEX IDX_FD8B3C73DAE07E97 ON c_blog_task_rel_user;');
        $this->addSql('DROP INDEX IDX_FD8B3C738DB60186 ON c_blog_task_rel_user;');
        $this->addSql('DROP INDEX IDX_FD8B3C73A76ED395 ON c_blog_task_rel_user;');
        $this->addSql('CREATE INDEX course ON c_blog_task_rel_user (c_id);');
        $this->addSql('CREATE INDEX task ON c_blog_task_rel_user (task_id);');
        $this->addSql('CREATE INDEX user ON c_blog_task_rel_user (user_id);');

        $this->addSql('ALTER TABLE c_blog DROP FOREIGN KEY FK_64B00A121BAD783F;');
        $this->addSql('ALTER TABLE c_blog ADD c_id INT NOT NULL, ADD blog_id INT NOT NULL, ADD visibility TINYINT(1) NOT NULL, ADD session_id INT DEFAULT NULL;');
        $this->addSql('DROP INDEX UNIQ_64B00A121BAD783F ON c_blog;');
        $this->addSql('CREATE INDEX course ON c_blog (c_id);');
        $this->addSql('CREATE INDEX session_id ON c_blog (session_id);');

        $this->addSql('ALTER TABLE c_lp_category_rel_user ADD CONSTRAINT FK_61F0427A76ED395 FOREIGN KEY (c_id) REFERENCES course (id);');

        $this->addSql('ALTER TABLE c_blog_task DROP FOREIGN KEY FK_BE09DF0BDAE07E97;');
        $this->addSql('ALTER TABLE c_blog_task ADD c_id INT NOT NULL;');
        $this->addSql('DROP INDEX IDX_BE09DF0BDAE07E97 ON c_blog_task;');
        $this->addSql('CREATE INDEX course ON c_blog_task (c_id);');

        $this->addSql('ALTER TABLE c_blog_comment DROP FOREIGN KEY FK_CAA18F1F675F31B;');
        $this->addSql('ALTER TABLE c_blog_comment DROP FOREIGN KEY FK_CAA18F1DAE07E97;');
        $this->addSql('ALTER TABLE c_blog_comment ADD c_id INT NOT NULL, ADD post_id INT NOT NULL, ADD task_id INT NOT NULL, ADD parent_comment_id INT NOT NULL;');
        $this->addSql('DROP INDEX IDX_CAA18F1F675F31B ON c_blog_comment;');
        $this->addSql('DROP INDEX IDX_CAA18F1DAE07E97 ON c_blog_comment;');
        $this->addSql('CREATE INDEX course ON c_blog_comment (c_id);');
    }
}
