<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;


/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150522112023 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
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
