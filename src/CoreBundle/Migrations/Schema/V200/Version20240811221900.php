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
        $this->addSql('ALTER TABLE block ADD user_id INT DEFAULT NULL;');
        $this->addSql('ALTER TABLE block ADD CONSTRAINT FK_831B9722A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_831B9722A76ED395 ON block (user_id);');

        $this->addSql('DROP INDEX course ON c_blog_attachment;');
        $this->addSql('ALTER TABLE c_blog_attachment DROP id, DROP c_id, DROP post_id, DROP comment_id, CHANGE blog_id blog_id INT DEFAULT NULL;');
        $this->addSql('DELETE FROM c_blog_attachment WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        $this->addSql('ALTER TABLE c_blog_attachment ADD CONSTRAINT FK_E769AADCDAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        $this->addSql('CREATE INDEX IDX_E769AADCDAE07E97 ON c_blog_attachment (blog_id);');

        $this->addSql('DROP INDEX course ON c_blog_rating;');
        $this->addSql('ALTER TABLE c_blog_rating DROP rating_id, DROP c_id, DROP item_id, CHANGE blog_id blog_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL;');
        $this->addSql('DELETE FROM c_blog_rating WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        $this->addSql('ALTER TABLE c_blog_rating ADD CONSTRAINT FK_D4E30760DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        $this->addSql('DELETE FROM c_blog_rating WHERE user_id NOT IN (SELECT id FROM user);');
        $this->addSql('ALTER TABLE c_blog_rating ADD CONSTRAINT FK_D4E30760A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        $this->addSql('CREATE INDEX IDX_D4E30760DAE07E97 ON c_blog_rating (blog_id);');
        $this->addSql('CREATE INDEX IDX_D4E30760A76ED395 ON c_blog_rating (user_id);');

        $this->addSql('DROP INDEX course ON c_blog_post;');
        $this->addSql('ALTER TABLE c_blog_post DROP c_id, DROP post_id, CHANGE blog_id blog_id INT DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL;');
        $this->addSql('DELETE FROM c_blog_post WHERE author_id NOT IN (SELECT id FROM user);');
        $this->addSql('ALTER TABLE c_blog_post ADD CONSTRAINT FK_B6FD68A3F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE;');
        $this->addSql('DELETE FROM c_blog_post WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        $this->addSql('ALTER TABLE c_blog_post ADD CONSTRAINT FK_B6FD68A3DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        $this->addSql('CREATE INDEX IDX_B6FD68A3F675F31B ON c_blog_post (author_id);');
        $this->addSql('CREATE INDEX IDX_B6FD68A3DAE07E97 ON c_blog_post (blog_id);');

        $this->addSql('DROP INDEX course ON c_blog_rel_user;');
        $this->addSql('ALTER TABLE c_blog_rel_user DROP c_id, CHANGE blog_id blog_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL;');
        $this->addSql('DELETE FROM c_blog_rel_user WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        $this->addSql('ALTER TABLE c_blog_rel_user ADD CONSTRAINT FK_B55D851BDAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        $this->addSql('DELETE FROM c_blog_rel_user WHERE user_id NOT IN (SELECT id FROM user);');
        $this->addSql('ALTER TABLE c_blog_rel_user ADD CONSTRAINT FK_B55D851BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        $this->addSql('CREATE INDEX IDX_B55D851BDAE07E97 ON c_blog_rel_user (blog_id);');
        $this->addSql('CREATE INDEX IDX_B55D851BA76ED395 ON c_blog_rel_user (user_id);');

        $this->addSql('DROP INDEX course ON c_blog_task_rel_user;');
        $this->addSql('DROP INDEX user ON c_blog_task_rel_user;');
        $this->addSql('DROP INDEX task ON c_blog_task_rel_user;');
        $this->addSql('ALTER TABLE c_blog_task_rel_user DROP c_id, CHANGE blog_id blog_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE task_id task_id INT DEFAULT NULL;');
        $this->addSql('DELETE FROM c_blog_task_rel_user WHERE task_id NOT IN (SELECT iid FROM c_blog_task);');
        $this->addSql('ALTER TABLE c_blog_task_rel_user ADD CONSTRAINT FK_FD8B3C738DB60186 FOREIGN KEY (task_id) REFERENCES c_blog_task (iid) ON DELETE CASCADE;');
        $this->addSql('DELETE FROM c_blog_task_rel_user WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        $this->addSql('ALTER TABLE c_blog_task_rel_user ADD CONSTRAINT FK_FD8B3C73DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        $this->addSql('DELETE FROM c_blog_task_rel_user WHERE user_id NOT IN (SELECT id FROM user);');
        $this->addSql('ALTER TABLE c_blog_task_rel_user ADD CONSTRAINT FK_FD8B3C73A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        $this->addSql('CREATE INDEX IDX_FD8B3C73DAE07E97 ON c_blog_task_rel_user (blog_id);');
        $this->addSql('CREATE INDEX IDX_FD8B3C738DB60186 ON c_blog_task_rel_user (task_id);');
        $this->addSql('CREATE INDEX IDX_FD8B3C73A76ED395 ON c_blog_task_rel_user (user_id);');

        $this->addSql('DROP INDEX course ON c_blog;');
        $this->addSql('DROP INDEX session_id ON c_blog;');
        $this->addSql('ALTER TABLE c_blog DROP c_id, DROP blog_id, DROP visibility, DROP session_id;');
        $this->addSql('ALTER TABLE c_blog ADD CONSTRAINT FK_64B00A121BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE c_lp_category_rel_user DROP FOREIGN KEY FK_61F0427A76ED395;');

        $this->addSql('DROP INDEX course ON c_blog_task;');
        $this->addSql('ALTER TABLE c_blog_task DROP c_id, CHANGE blog_id blog_id INT DEFAULT NULL;');
        $this->addSql('DELETE FROM c_blog_task WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        $this->addSql('ALTER TABLE c_blog_task ADD CONSTRAINT FK_BE09DF0BDAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        $this->addSql('CREATE INDEX IDX_BE09DF0BDAE07E97 ON c_blog_task (blog_id);');

        $this->addSql('DROP INDEX course ON c_blog_comment;');
        $this->addSql('ALTER TABLE c_blog_comment DROP c_id, DROP post_id, DROP task_id, DROP parent_comment_id, CHANGE author_id author_id INT DEFAULT NULL, CHANGE blog_id blog_id INT DEFAULT NULL;');
        $this->addSql('DELETE FROM c_blog_comment WHERE author_id NOT IN (SELECT id FROM user);');
        $this->addSql('ALTER TABLE c_blog_comment ADD CONSTRAINT FK_CAA18F1F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE;');
        $this->addSql('DELETE FROM c_blog_comment WHERE blog_id NOT IN (SELECT iid FROM c_blog);');
        $this->addSql('ALTER TABLE c_blog_comment ADD CONSTRAINT FK_CAA18F1DAE07E97 FOREIGN KEY (blog_id) REFERENCES c_blog (iid) ON DELETE CASCADE;');
        $this->addSql('CREATE INDEX IDX_CAA18F1F675F31B ON c_blog_comment (author_id);');
        $this->addSql('CREATE INDEX IDX_CAA18F1DAE07E97 ON c_blog_comment (blog_id);');
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
