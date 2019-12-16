<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Version20150615171900 class
 * Change in CCourseDescription entity.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class Version20150615171900 extends AbstractMigrationChamilo
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_course_description CHANGE description_type description_type INTEGER NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_course_description CHANGE description_type description_type BOOLEAN NOT NULL');
    }
}
