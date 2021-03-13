<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Access url.
 */
class Version20170628122900 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        // access_url_rel_user.
        $table = $schema->getTable('access_url_rel_user');
        if (false === $table->hasColumn('id')) {
            $this->addSql('ALTER TABLE access_url_rel_user MODIFY COLUMN access_url_id INT NOT NULL');
            $this->addSql('ALTER TABLE access_url_rel_user MODIFY COLUMN user_id INT NOT NULL');
            if ($table->hasPrimaryKey()) {
                $this->addSql('ALTER TABLE access_url_rel_user DROP PRIMARY KEY');
            }

            $this->addSql(
                'ALTER TABLE access_url_rel_user ADD id INT AUTO_INCREMENT NOT NULL, ADD PRIMARY KEY (id)'
            );

            $this->addSql('ALTER TABLE access_url_rel_user CHANGE access_url_id access_url_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE access_url_rel_user CHANGE user_id user_id INT DEFAULT NULL');
        }

        if ($table->hasForeignKey('FK_85574263A76ED395')) {
            $this->addSql('ALTER TABLE access_url_rel_user DROP FOREIGN KEY FK_85574263A76ED395');
            $this->addSql(
                'ALTER TABLE access_url_rel_user ADD CONSTRAINT FK_85574263A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        } else {
            $this->addSql(
                'ALTER TABLE access_url_rel_user ADD CONSTRAINT FK_85574263A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        // access_url_rel_session.
        $table = $schema->getTable('access_url_rel_session');
        if (false === $table->hasColumn('id')) {
            $this->addSql('ALTER TABLE access_url_rel_session DROP PRIMARY KEY');
            $this->addSql(
                'ALTER TABLE access_url_rel_session ADD id INT AUTO_INCREMENT NOT NULL, CHANGE access_url_id access_url_id INT DEFAULT NULL, CHANGE session_id session_id INT DEFAULT NULL, ADD PRIMARY KEY (id);'
            );
            $this->addSql(
                'ALTER TABLE access_url_rel_session ADD CONSTRAINT FK_6CBA5F5D613FECDF FOREIGN KEY (session_id) REFERENCES session (id);'
            );
            $this->addSql(
                'ALTER TABLE access_url_rel_session ADD CONSTRAINT FK_6CBA5F5D73444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id);'
            );
            $this->addSql('CREATE INDEX IDX_6CBA5F5D613FECDF ON access_url_rel_session (session_id);');
            $this->addSql('CREATE INDEX IDX_6CBA5F5D73444FD5 ON access_url_rel_session (access_url_id);');
        }

        // access_url.
        $table = $schema->getTable('access_url');
        if (false === $table->hasColumn('limit_courses')) {
            $this->addSql(
                'ALTER TABLE access_url ADD limit_courses INT DEFAULT NULL, ADD limit_active_courses INT DEFAULT NULL, ADD limit_sessions INT DEFAULT NULL, ADD limit_users INT DEFAULT NULL, ADD limit_teachers INT DEFAULT NULL, ADD limit_disk_space INT DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL;'
            );
        }
        $this->addSql(
            'ALTER TABLE access_url_rel_course_category CHANGE access_url_id access_url_id INT DEFAULT NULL, CHANGE course_category_id course_category_id INT DEFAULT NULL'
        );

        if (false === $table->hasColumn('parent_id')) {
            $this->addSql('ALTER TABLE access_url ADD parent_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE access_url ADD CONSTRAINT FK_9436187B727ACA70 FOREIGN KEY (parent_id) REFERENCES access_url (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE INDEX IDX_9436187B727ACA70 ON access_url (parent_id);');
        }

        if (false === $table->hasColumn('tree_root')) {
            $this->addSql('ALTER TABLE access_url ADD tree_root INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE access_url ADD CONSTRAINT FK_9436187BA977936C FOREIGN KEY (tree_root) REFERENCES access_url (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE INDEX IDX_9436187BA977936C ON access_url (tree_root);');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE access_url ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE access_url ADD CONSTRAINT FK_9436187B1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_9436187B1BAD783F ON access_url (resource_node_id);');
        }

        if (false === $table->hasColumn('lft')) {
            $this->addSql('ALTER TABLE access_url ADD lft INT NOT NULL');
        }

        if (false === $table->hasColumn('lvl')) {
            $this->addSql('ALTER TABLE access_url ADD lvl INT NOT NULL');
        }

        if (false === $table->hasColumn('rgt')) {
            $this->addSql('ALTER TABLE access_url ADD rgt INT NOT NULL;');
        }

        // access_url_rel_course_category.
        $table = $schema->getTable('access_url_rel_course_category');
        if (false === $table->hasForeignKey('FK_3545C2A673444FD5')) {
            $this->addSql(
                'ALTER TABLE access_url_rel_course_category ADD CONSTRAINT FK_3545C2A673444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id)'
            );
        }
        if (false === $table->hasForeignKey('FK_3545C2A66628AD36')) {
            $this->addSql(
                'ALTER TABLE access_url_rel_course_category ADD CONSTRAINT FK_3545C2A66628AD36 FOREIGN KEY (course_category_id) REFERENCES course_category (id)'
            );
        }
        if (false === $table->hasIndex('IDX_3545C2A673444FD5')) {
            $this->addSql('CREATE INDEX IDX_3545C2A673444FD5 ON access_url_rel_course_category (access_url_id)');
        }
        if (false === $table->hasIndex('IDX_3545C2A66628AD36')) {
            $this->addSql('CREATE INDEX IDX_3545C2A66628AD36 ON access_url_rel_course_category (course_category_id)');
        }

        $this->addSql('ALTER TABLE access_url_rel_usergroup CHANGE access_url_id access_url_id INT DEFAULT NULL');

        // access_url_rel_usergroup.
        $table = $schema->getTable('access_url_rel_usergroup');
        if (!$table->hasForeignKey('FK_AD488DD573444FD5')) {
            $this->addSql(
                'ALTER TABLE access_url_rel_usergroup ADD CONSTRAINT FK_AD488DD573444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id)'
            );
        }
        if (!$table->hasIndex('IDX_AD488DD573444FD5')) {
            $this->addSql('CREATE INDEX IDX_AD488DD573444FD5 ON access_url_rel_usergroup (access_url_id)');
        }

        $this->addSql('ALTER TABLE access_url_rel_usergroup CHANGE usergroup_id usergroup_id INT DEFAULT NULL;');

        if (!$table->hasForeignKey('FK_AD488DD5D2112630')) {
            $this->addSql(
                'ALTER TABLE access_url_rel_usergroup ADD CONSTRAINT FK_AD488DD5D2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id) ON DELETE CASCADE;'
            );
        }
        if (!$table->hasIndex('IDX_AD488DD5D2112630')) {
            $this->addSql('CREATE INDEX IDX_AD488DD5D2112630 ON access_url_rel_usergroup (usergroup_id);');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
