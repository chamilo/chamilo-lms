<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20191101132000.
 */
class Version20191101132000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $this->getEntityManager();

        $this->addSql('ALTER TABLE course ADD category_id INT DEFAULT NULL;');
        $this->addSql('UPDATE course co SET co.category_id = (SELECT cat.id FROM course_category cat WHERE cat.code = co.category_code)');
        $this->addSql('DROP INDEX category_code ON course');
        $this->addSql('ALTER TABLE course DROP category_code');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB912469DE2 FOREIGN KEY (category_id) REFERENCES course_category (id)');
        $this->addSql('CREATE INDEX IDX_169E6FB912469DE2 ON course (category_id)');
    }

    public function down(Schema $schema): void
    {
    }
}
