<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170626122900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'User changes';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->getEntityManager()->getConnection();

        $table = $schema->getTable('user');

        if ($table->hasIndex('idx_user_uid')) {
            $this->addSql('DROP INDEX idx_user_uid ON user;');
        }

        if ($table->hasIndex('UNIQ_8D93D649C05FB297')) {
            $this->addSql('DROP INDEX UNIQ_8D93D649C05FB297 ON user;');
        }

        if ($table->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE user DROP user_id');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE user ADD resource_node_id BIGINT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE user ADD CONSTRAINT FK_8D93D6491BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6491BAD783F ON user (resource_node_id);');
        }

        if ($table->hasColumn('salt')) {
            $this->addSql('ALTER TABLE user CHANGE salt salt VARCHAR(255) NOT NULL');
        }

        if ($table->hasColumn('created_at')) {
            $this->addSql(
                'UPDATE user SET created_at = registration_date WHERE CAST(created_at AS CHAR(20)) = "0000-00-00 00:00:00"'
            );
            $this->addSql('UPDATE user SET created_at = registration_date WHERE created_at IS NULL');
            //$this->addSql('UPDATE user SET created_at = NOW() WHERE created_at = NULL OR created_at = ""');
            $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME NOT NULL');
        }

        if ($table->hasColumn('updated_at')) {
            $this->addSql(
                'UPDATE user SET updated_at = registration_date WHERE CAST(updated_at AS CHAR(20)) = "0000-00-00 00:00:00"'
            );
            $this->addSql('UPDATE user SET updated_at = registration_date WHERE updated_at IS NULL');
            //$this->addSql('UPDATE user SET updated_at = NOW() WHERE updated_at = NULL OR updated_at = ""');
            $this->addSql('ALTER TABLE user CHANGE updated_at updated_at DATETIME NOT NULL');
        }

        if ($table->hasColumn('confirmation_token')) {
            $this->addSql('ALTER TABLE user CHANGE confirmation_token confirmation_token VARCHAR(255) DEFAULT NULL');
        }

        if ($table->hasColumn('website')) {
            $this->addSql('ALTER TABLE user CHANGE website website VARCHAR(255) DEFAULT NULL');
        } else {
            $this->addSql('ALTER TABLE user ADD website VARCHAR(255) DEFAULT NULL');
        }

        if (false === $table->hasColumn('api_token')) {
            $this->addSql('ALTER TABLE user ADD api_token VARCHAR(255) DEFAULT NULL');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6497BA2F5EB ON user (api_token);');
        }

        if (false === $table->hasColumn('date_of_birth')) {
            $this->addSql('ALTER TABLE user ADD date_of_birth DATETIME DEFAULT NULL');
        }
        if (false === $table->hasColumn('biography')) {
            $this->addSql('ALTER TABLE user ADD biography LONGTEXT DEFAULT NULL');
        }
        if (false === $table->hasColumn('gender')) {
            $this->addSql('ALTER TABLE user ADD gender VARCHAR(1) DEFAULT NULL');
        }

        if (false === $table->hasColumn('locale')) {
            $this->addSql('ALTER TABLE user ADD locale VARCHAR(10) NOT NULL');
            $this->addSql('UPDATE user SET language = "english" WHERE language IS NULL OR language = "" ');
            $this->addSql('UPDATE user SET locale = (SELECT isocode FROM language WHERE english_name = language)');
        }

        if (false === $table->hasColumn('timezone')) {
            $this->addSql('ALTER TABLE user ADD timezone VARCHAR(64) NOT NULL');
        }

        if (false === $table->hasColumn('confirmation_token')) {
            $this->addSql('ALTER TABLE user ADD confirmation_token VARCHAR(255) DEFAULT NULL');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C05FB297 ON user (confirmation_token)');
        } else {
            $this->addSql('ALTER TABLE user CHANGE confirmation_token confirmation_token VARCHAR(255) DEFAULT NULL');
        }

        $this->addSql('ALTER TABLE user CHANGE username_canonical username_canonical VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE lastname lastname VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE firstname firstname VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE phone phone VARCHAR(64) DEFAULT NULL');

        $table = $schema->getTable('admin');
        $this->addSql('ALTER TABLE admin CHANGE user_id user_id INT DEFAULT NULL');
        if (false === $table->hasForeignKey('FK_880E0D76A76ED395')) {
            $this->addSql(
                'ALTER TABLE admin ADD CONSTRAINT FK_880E0D76A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if ($table->hasIndex('user_id')) {
            //$this->addSql('DROP INDEX user_id ON admin');
        }

        if (false === $table->hasIndex('UNIQ_880E0D76A76ED395')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_880E0D76A76ED395 ON admin (user_id)');
        }

        $table = $schema->getTable('user_course_category');
        if (false === $table->hasColumn('collapsed')) {
            $this->addSql('ALTER TABLE user_course_category ADD collapsed TINYINT(1) DEFAULT NULL');
        }
        $this->addSql('ALTER TABLE user_course_category CHANGE user_id user_id INT DEFAULT NULL');

        if (false === $table->hasForeignKey('FK_BD241818A76ED395')) {
            $this->addSql(
                'ALTER TABLE user_course_category ADD CONSTRAINT FK_BD241818A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        $table = $schema->getTable('user_rel_course_vote');
        $this->addSql('ALTER TABLE user_rel_course_vote CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('DELETE FROM user_rel_course_vote WHERE user_id NOT IN (SELECT id FROM user)');

        $this->addSql('ALTER TABLE user_rel_course_vote CHANGE c_id c_id INT DEFAULT NULL');
        $this->addSql('DELETE FROM user_rel_course_vote WHERE c_id NOT IN (SELECT id FROM course)');

        $this->addSql('ALTER TABLE user_rel_course_vote CHANGE session_id session_id INT DEFAULT NULL');
        $this->addSql('UPDATE user_rel_course_vote SET session_id = NULL WHERE session_id = 0 ');

        $this->addSql('DELETE FROM user_rel_course_vote WHERE session_id IS NOT NULL AND session_id NOT IN (SELECT id FROM session)');

        $this->addSql('ALTER TABLE user_rel_course_vote CHANGE url_id url_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_4038AA47A76ED395')) {
            $this->addSql(
                'ALTER TABLE user_rel_course_vote ADD CONSTRAINT FK_4038AA47A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_4038AA4791D79BD3')) {
            $this->addSql(
                'ALTER TABLE user_rel_course_vote ADD CONSTRAINT FK_4038AA4791D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
            );
        }
        if (!$table->hasForeignKey('FK_4038AA47613FECDF')) {
            $this->addSql(
                'ALTER TABLE user_rel_course_vote ADD CONSTRAINT FK_4038AA47613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_4038AA4781CFDAE7')) {
            $this->addSql(
                'ALTER TABLE user_rel_course_vote ADD CONSTRAINT FK_4038AA4781CFDAE7 FOREIGN KEY (url_id) REFERENCES access_url (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_4038AA47613FECDF')) {
            $this->addSql('CREATE INDEX IDX_4038AA47613FECDF ON user_rel_course_vote (session_id)');
        }
        if (!$table->hasIndex('IDX_4038AA4781CFDAE7')) {
            $this->addSql('CREATE INDEX IDX_4038AA4781CFDAE7 ON user_rel_course_vote (url_id)');
        }

        $table = $schema->getTable('user_rel_tag');
        $this->addSql('ALTER TABLE user_rel_tag CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('DELETE FROM user_rel_tag WHERE user_id NOT IN (SELECT id FROM user)');

        if (!$table->hasForeignKey('FK_D5CB75B6A76ED395')) {
            $this->addSql(
                'ALTER TABLE user_rel_tag ADD CONSTRAINT FK_D5CB75B6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }
        $this->addSql('ALTER TABLE user_rel_tag CHANGE tag_id tag_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_D5CB75B6BAD26311')) {
            $this->addSql(
                'ALTER TABLE user_rel_tag ADD CONSTRAINT FK_D5CB75B6BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE'
            );
        }

        $table = $schema->getTable('user_rel_user');
        $this->addSql('DELETE FROM user_rel_user WHERE user_id = 0 OR friend_user_id = 0');
        $this->addSql('DELETE FROM user_rel_user WHERE user_id IS NULL OR friend_user_id IS NULL');
        $this->addSql('DELETE FROM user_rel_user WHERE user_id NOT IN (SELECT id FROM user)');
        $this->addSql('DELETE FROM user_rel_user WHERE friend_user_id NOT IN (SELECT id FROM user)');

        $this->addSql('ALTER TABLE user_rel_user CHANGE friend_user_id friend_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_rel_user CHANGE user_id user_id INT NOT NULL');

        if ($table->hasColumn('last_edit')) {
            $this->addSql('UPDATE user_rel_user SET last_edit = NOW() WHERE last_edit IS NULL');
        }

        // Remove duplicates.
        $sql = 'SELECT max(id) id, user_id, friend_user_id, relation_type, count(*) as count
                FROM user_rel_user
                GROUP BY user_id, friend_user_id, relation_type
                HAVING count > 1';
        $result = $connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();

        foreach ($items as $item) {
            $userId = $item['user_id'];
            $friendId = $item['friend_user_id'];
            $relationType = $item['relation_type'];

            $sql = "SELECT id
                    FROM user_rel_user
                    WHERE user_id = $userId AND friend_user_id = $friendId AND relation_type = $relationType ";
            $result = $connection->executeQuery($sql);
            $subItems = $result->fetchAllAssociative();
            $counter = 0;
            foreach ($subItems as $subItem) {
                $id = $subItem['id'];
                if (0 === $counter) {
                    $counter++;

                    continue;
                }
                $sql = "DELETE FROM user_rel_user WHERE id = $id";
                $this->addSql($sql);
                $counter++;
            }
        }

        $this->addSql(
            'UPDATE user_rel_user SET last_edit = NOW() WHERE CAST(last_edit AS CHAR(20)) = "0000-00-00 00:00:00"'
        );

        if (!$table->hasIndex('user_friend_relation')) {
            $this->addSql('CREATE UNIQUE INDEX user_friend_relation ON user_rel_user (user_id, friend_user_id, relation_type)');
        }

        if (!$table->hasColumn('created_at')) {
            $this->addSql("ALTER TABLE user_rel_user ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime)' ");
            $this->addSql('UPDATE user_rel_user SET created_at = last_edit');
        }

        if (!$table->hasColumn('updated_at')) {
            $this->addSql("ALTER TABLE user_rel_user ADD updated_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime)' ");
            $this->addSql('UPDATE user_rel_user SET updated_at = last_edit');
        }

        if (!$table->hasForeignKey('FK_DBF650A893D1119E')) {
            $this->addSql(
                ' ALTER TABLE user_rel_user ADD CONSTRAINT FK_DBF650A893D1119E FOREIGN KEY (friend_user_id) REFERENCES user (id) ON DELETE CASCADE;'
            );
        }

        if (false === $table->hasForeignKey('FK_DBF650A8A76ED395')) {
            $this->addSql(
                'ALTER TABLE user_rel_user ADD CONSTRAINT FK_DBF650A8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
