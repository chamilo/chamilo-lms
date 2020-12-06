<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Uid\Uuid;

/**
 * User.
 */
class Version20170626122900 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user');
        if (false === $table->hasIndex('idx_user_uid')) {
            $this->addSql('DROP INDEX idx_user_uid ON user;');
        }
        if ($table->hasIndex('UNIQ_8D93D649C05FB297')) {
            $this->addSql('DROP INDEX UNIQ_8D93D649C05FB297 ON user;');
        }
        if ($table->hasIndex('idx_user_uid')) {
            $this->addSql('DROP INDEX idx_user_uid ON user');
        }

        if ($table->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE user DROP user_id');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE user ADD resource_node_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE user ADD CONSTRAINT FK_8D93D6491BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6491BAD783F ON user (resource_node_id);');
        }


        $this->addSql(
            'ALTER TABLE user CHANGE salt salt VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL'
        );
        $this->addSql(
            'ALTER TABLE user CHANGE confirmation_token confirmation_token VARCHAR(255) DEFAULT NULL, CHANGE website website VARCHAR(255) DEFAULT NULL'
        );

        if (false === $table->hasColumn('user_id')) {

        }
        if (false === $table->hasColumn('user_id')) {

        }


        $em = $this->getEntityManager();

        if (false === $table->hasColumn('uuid')) {
            $this->addSql("ALTER TABLE user ADD uuid BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'");
            $sql = 'SELECT * FROM user';
            $result = $em->getConnection()->executeQuery($sql);
            $data = $result->fetchAllAssociative();
            foreach ($data as $item) {
                $uuid = Uuid::v4()->toBinary();
                $userId = $item['id'];
                $sql = "UPDATE user SET uuid = '$uuid' WHERE id = $userId";
                $this->addSql($sql);
            }
        }

        if (false === $table->hasIndex('UNIQ_8D93D649D17F50A6')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D17F50A6 ON user (uuid);');
        }

        if (false === $table->hasColumn('api_token')) {
            $this->addSql('ALTER TABLE user ADD api_token VARCHAR(255) DEFAULT NULL');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6497BA2F5EB ON user (api_token);');
        }

        if (false === $table->hasColumn('date_of_birth')) {
            $this->addSql('ALTER TABLE user ADD date_of_birth DATETIME DEFAULT NULL');
        }
        if (false === $table->hasColumn('website')) {
            $this->addSql('ALTER TABLE user ADD website VARCHAR(255) DEFAULT NULL');
        }
        if (false === $table->hasColumn('biography')) {
            $this->addSql('ALTER TABLE user ADD biography LONGTEXT DEFAULT NULL');
        }
        if (false === $table->hasColumn('gender')) {
            $this->addSql('ALTER TABLE user ADD gender VARCHAR(1) DEFAULT NULL');
        }
        if (false === $table->hasColumn('locale')) {
            $this->addSql('ALTER TABLE user ADD locale VARCHAR(8) DEFAULT NULL');
        }
        if (false === $table->hasColumn('timezone')) {
            $this->addSql('ALTER TABLE user ADD timezone VARCHAR(64) NOT NULL');
        }

        if (false === $table->hasColumn('confirmation_token')) {
            $this->addSql('ALTER TABLE user ADD confirmation_token VARCHAR(255) DEFAULT NULL');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C05FB297 ON user (confirmation_token)');
        } else {
            $this->addSql('ALTER TABLE user CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL');
        }

        $this->addSql('ALTER TABLE user CHANGE username_canonical username_canonical VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE lastname lastname VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE firstname firstname VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE phone phone VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE salt salt VARCHAR(255) DEFAULT NULL');
        $this->addSql(
            'UPDATE user SET created_at = registration_date WHERE CAST(created_at AS CHAR(20)) = "0000-00-00 00:00:00"'
        );
        $this->addSql(
            'UPDATE user SET updated_at = registration_date WHERE CAST(updated_at AS CHAR(20)) = "0000-00-00 00:00:00"'
        );

        $table = $schema->getTable('admin');
        $this->addSql('ALTER TABLE admin CHANGE user_id user_id INT DEFAULT NULL');
        if (false === $table->hasForeignKey('FK_880E0D76A76ED395')) {
            $this->addSql(
                'ALTER TABLE admin ADD CONSTRAINT FK_880E0D76A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (false === $table->hasIndex('user_id')) {
            $this->addSql('DROP INDEX user_id ON admin');
        }

        if (false === $table->hasIndex('UNIQ_880E0D76A76ED395')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_880E0D76A76ED395 ON admin (user_id)');
        }

        $table = $schema->getTable('user_course_category');
        if (!$table->hasColumn('collapsed')) {
            $this->addSql('ALTER TABLE user_course_category ADD collapsed TINYINT(1) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
    }

    public function getDescription(): string
    {
        return 'User changes';
    }
}
