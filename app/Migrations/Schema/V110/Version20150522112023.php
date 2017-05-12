<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Usergroup changes
 */
class Version20150522112023 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Set 0 if there's no group category.
        //$this->addSql('UPDATE c_group_info SET category_id = 0 WHERE category_id = 2');

        $this->addSql('ALTER TABLE usergroup ADD group_type INT NOT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE usergroup ADD picture VARCHAR(255) DEFAULT NULL, ADD url VARCHAR(255) DEFAULT NULL, ADD visibility VARCHAR(255) NOT NULL, ADD allow_members_leave_group INT NOT NULL, CHANGE description description LONGTEXT');

        if (!$schema->hasTable('usergroup_rel_usergroup')) {
            $this->addSql(
                'CREATE TABLE usergroup_rel_usergroup (id INT AUTO_INCREMENT NOT NULL, group_id INT NOT NULL, subgroup_id INT NOT NULL, relation_type INT NOT NULL, PRIMARY KEY(id))'
            );
        }

        $this->addSql('ALTER TABLE usergroup_rel_user ADD relation_type INT');

        if (!$schema->hasTable('access_url_rel_usergroup')) {
            $this->addSql(
                'CREATE TABLE access_url_rel_usergroup (id INT AUTO_INCREMENT NOT NULL, access_url_id INT NOT NULL, usergroup_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $sql = 'SELECT * FROM usergroup';
            $result = $this->connection->query($sql);
            $results = $result->fetchAll();
            foreach ($results as $result) {
                $groupId = $result['id'];
                $sql = "INSERT INTO access_url_rel_usergroup (access_url_id, usergroup_id) VALUES ('1', '$groupId')";
                $this->addSql($sql);
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE access_url_rel_usergroup');
    }
}
