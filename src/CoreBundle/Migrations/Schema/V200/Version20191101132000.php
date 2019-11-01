<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20191101132000.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V200
 */
class Version20191101132000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->getEntityManager();

        $this->addSql('ALTER TABLE course ADD category INT DEFAULT NULL');
        $this->addSql('UPDATE course co SET co.category = (SELECT cat.id FROM course_category cat WHERE cat.code = co.category_code)');
        $this->addSql('DROP INDEX category_code ON course');
        $this->addSql('ALTER TABLE course DROP category_code');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB964C19C1 FOREIGN KEY (category) REFERENCES course_category (id)');
        $this->addSql('CREATE INDEX IDX_169E6FB964C19C1 ON course (category)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
