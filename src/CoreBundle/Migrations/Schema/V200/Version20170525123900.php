<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170525123900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'usergroup changes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('usergroup');
        if (!$table->hasForeignKey('FK_4A6478171BAD783F')) {
            $this->addSql('ALTER TABLE usergroup ADD CONSTRAINT FK_4A6478171BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
        }

        if (!$table->hasIndex('UNIQ_4A6478171BAD783F')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_4A6478171BAD783F ON usergroup (resource_node_id)');
        }

        $table = $schema->getTable('usergroup_rel_course');

        $this->addSql('ALTER TABLE usergroup_rel_course CHANGE usergroup_id usergroup_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE usergroup_rel_course CHANGE course_id course_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_4A8DF159D2112630')) {
            $this->addSql(
                'ALTER TABLE usergroup_rel_course ADD CONSTRAINT FK_4A8DF159D2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_4A8DF159591CC992')) {
            $this->addSql(
                'ALTER TABLE usergroup_rel_course ADD CONSTRAINT FK_4A8DF159591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_4A8DF159D2112630')) {
            $this->addSql('CREATE INDEX IDX_4A8DF159D2112630 ON usergroup_rel_course (usergroup_id)');
        }
        if (!$table->hasIndex('IDX_4A8DF159591CC992')) {
            $this->addSql('CREATE INDEX IDX_4A8DF159591CC992 ON usergroup_rel_course (course_id)');
        }

        $table = $schema->getTable('usergroup_rel_question');

        $this->addSql('ALTER TABLE usergroup_rel_question CHANGE question_id question_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE usergroup_rel_question CHANGE usergroup_id usergroup_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_FF3E58F21E27F6BF')) {
            $this->addSql(
                'ALTER TABLE usergroup_rel_question ADD CONSTRAINT FK_FF3E58F21E27F6BF FOREIGN KEY (question_id) REFERENCES c_quiz_question (iid) ON DELETE CASCADE'
            );
        }
        if (!$table->hasForeignKey('FK_FF3E58F2D2112630')) {
            $this->addSql(
                'ALTER TABLE usergroup_rel_question ADD CONSTRAINT FK_FF3E58F2D2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_FF3E58F21E27F6BF')) {
            $this->addSql('CREATE INDEX IDX_FF3E58F21E27F6BF ON usergroup_rel_question (question_id)');
        }

        if (!$table->hasIndex('IDX_FF3E58F2D2112630')) {
            $this->addSql('CREATE INDEX IDX_FF3E58F2D2112630 ON usergroup_rel_question (usergroup_id)');
        }

        $table = $schema->getTable('usergroup_rel_session');

        $this->addSql('ALTER TABLE usergroup_rel_session CHANGE usergroup_id usergroup_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE usergroup_rel_session CHANGE session_id session_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_70122432D2112630')) {
            $this->addSql(
                'ALTER TABLE usergroup_rel_session ADD CONSTRAINT FK_70122432D2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id) ON DELETE CASCADE'
            );
        }
        if (!$table->hasForeignKey('FK_70122432613FECDF')) {
            $this->addSql(
                'ALTER TABLE usergroup_rel_session ADD CONSTRAINT FK_70122432613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_70122432D2112630')) {
            $this->addSql('CREATE INDEX IDX_70122432D2112630 ON usergroup_rel_session (usergroup_id)');
        }
        if (!$table->hasIndex('IDX_70122432613FECDF')) {
            $this->addSql('CREATE INDEX IDX_70122432613FECDF ON usergroup_rel_session (session_id)');
        }

        $table = $schema->getTable('usergroup_rel_user');

        $this->addSql('ALTER TABLE usergroup_rel_user CHANGE usergroup_id usergroup_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE usergroup_rel_user CHANGE user_id user_id INT DEFAULT NULL');

        if ($table->hasForeignKey('FK_739515A9A76ED395')) {
            $this->addSql('ALTER TABLE usergroup_rel_user DROP FOREIGN KEY FK_739515A9A76ED395');
        }
        $this->addSql(
            'ALTER TABLE usergroup_rel_user ADD CONSTRAINT FK_739515A9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;'
        );

        if ($table->hasForeignKey('FK_739515A9D2112630')) {
            $this->addSql('ALTER TABLE usergroup_rel_user DROP FOREIGN KEY FK_739515A9D2112630');
        }

        $this->addSql(
            'ALTER TABLE usergroup_rel_user ADD CONSTRAINT FK_739515A9D2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id) ON DELETE CASCADE;'
        );
    }

    public function down(Schema $schema): void
    {
    }
}
