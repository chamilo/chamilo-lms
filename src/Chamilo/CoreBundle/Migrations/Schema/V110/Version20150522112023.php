<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
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
        $this->addSql('UPDATE c_group_info SET category_id = 0 WHERE category_id = 2');

        $this->addSql('ALTER TABLE usergroup ADD group_type INT NOT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE usergroup ADD picture VARCHAR(255) DEFAULT NULL, ADD url VARCHAR(255) DEFAULT NULL, ADD visibility VARCHAR(255) NOT NULL, ADD allow_members_leave_group INT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE TABLE usergroup_rel_usergroup (id INT AUTO_INCREMENT NOT NULL, group_id INT NOT NULL, subgroup_id INT NOT NULL, relation_type INT NOT NULL, PRIMARY KEY(id));');

        $connection = $this->connection;

        $sql = "SELECT * FROM groups";
        $result = $connection->executeQuery($sql);
        $groups = $result->fetchAll();

        $oldGroups = array();

        if (!empty($groups )) {
            foreach ($groups as $group) {
                $sql = "INSERT INTO usergroup (name, group_type, description, picture, url, visibility, updated_at, created_at)
                        VALUES ('{$group['name']}', '1', '{$group['description']}', '{$group['picture_uri']}', '{$group['url']}', '{$group['visibility']}', '{$group['updated_on']}', '{$group['created_on']}')";

                $connection->executeQuery($sql);
                $id = $connection->lastInsertId('id');
                $oldGroups[$group['id']] = $id;
            }
        }

        if (!empty($oldGroups)) {
            foreach ($oldGroups as $oldId => $newId) {
                $path = \GroupPortalManager::get_group_picture_path_by_id(
                    $oldId,
                    'system'
                );
                if (!empty($path)) {

                    $newPath = str_replace(
                        "groups/$oldId/",
                        "groups/$newId/",
                        $path['dir']
                    );
                    $command = "mv {$path['dir']} $newPath ";
                    system($command);
                }
            }

            $sql = "SELECT * FROM group_rel_user";
            $result = $connection->executeQuery($sql);
            $dataList = $result->fetchAll();

            if (!empty($dataList)) {
                foreach ($dataList as $data) {
                    if (isset($oldGroups[$data['group_id']])) {
                        $data['group_id'] = $oldGroups[$data['group_id']];
                        $sql = "INSERT INTO usergroup_rel_user (usergroup_id, user_id, relation_type)
                                VALUES ('{$data['group_id']}', '{$data['user_id']}', '{$data['relation_type']}')";
                        $connection->executeQuery($sql);
                    }
                }
            }

            $sql = "SELECT * FROM group_rel_group";
            $result = $connection->executeQuery($sql);
            $dataList = $result->fetchAll();

            if (!empty($dataList)) {
                foreach ($dataList as $data) {
                    if (isset($oldGroups[$data['group_id']]) && isset($oldGroups[$data['subgroup_id']])) {
                        $data['group_id'] = $oldGroups[$data['group_id']];
                        $data['subgroup_id'] = $oldGroups[$data['subgroup_id']];
                        $sql = "INSERT INTO usergroup_rel_usergroup (group_id, subgroup_id, relation_type)
                                VALUES ('{$data['group_id']}', '{$data['subgroup_id']}', '{$data['relation_type']}')";
                        $connection->executeQuery($sql);
                    }
                }
            }

            $sql = "SELECT * FROM announcement_rel_group";
            $result = $connection->executeQuery($sql);
            $dataList = $result->fetchAll();

            if (!empty($dataList)) {
                foreach ($dataList as $data) {
                    if (isset($oldGroups[$data['group_id']])) {
                        //Deleting relation
                        $sql = "DELETE FROM announcement_rel_group WHERE id = {$data['id']}";
                        $connection->executeQuery($sql);

                        //Add new relation
                        $data['group_id'] = $oldGroups[$data['group_id']];
                        $sql = "INSERT INTO announcement_rel_group(group_id, announcement_id)
                                VALUES ('{$data['group_id']}', '{$data['announcement_id']}')";
                        $connection->executeQuery($sql);
                    }
                }
            }

            $sql = "SELECT * FROM group_rel_tag";
            $result = $connection->executeQuery($sql);
            $dataList = $result->fetchAll();
            if (!empty($dataList)) {
                foreach ($dataList as $data) {
                    if (isset($oldGroups[$data['group_id']])) {
                        $data['group_id'] = $oldGroups[$data['group_id']];
                        $sql = "INSERT INTO usergroup_rel_tag (tag_id, usergroup_id)
                            VALUES ('{$data['tag_id']}', '{$data['group_id']}')";
                        $connection->executeQuery($sql);
                    }
                }
            }
        }

        if (!$schema->hasTable('access_url_rel_usergroup')) {
            $this->addSql(
                'CREATE TABLE access_url_rel_usergroup (id INT UNSIGNED AUTO_INCREMENT NOT NULL, access_url_id INT UNSIGNED NOT NULL, usergroup_id INT UNSIGNED NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
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
