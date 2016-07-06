<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version20160706182000
 * Add new table to save user visibility on courses in the catalogue
 * @package Application\Migrations\Schema\V111
 */
class Version20160706182000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
         $this->addSql(
             'CREATE TABLE course_rel_user_catalogue (id int(11) NOT NULL AUTO_INCREMENT, user_id int(11) DEFAULT NULL, c_id int(11) DEFAULT NULL, visible int(11) NOT NULL, PRIMARY KEY (id), KEY (user_id), KEY (c_id), CONSTRAINT FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE, CONSTRAINT FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci'
             );
    }

     /**
      * @param Schema $schema
      *
      * @throws \Doctrine\DBAL\DBALException
      * @throws \Doctrine\DBAL\Schema\SchemaException
      */
    public function down(Schema $schema)
    {
        $this->addSql(
         'DROP TABLE course_rel_user_catalogue'
         );
    }
}