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
    }

    public function down(Schema $schema): void
    {
    }
}
