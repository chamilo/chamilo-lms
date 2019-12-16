<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160705192000
 * Add accumulate scorm time to c_lp table.
 */
class Version20160705192000 extends AbstractMigrationChamilo
{
    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $em = $this->getEntityManager();

        $result = $em
            ->getRepository('ChamiloCoreBundle:SettingsCurrent')
            ->findOneBy(['variable' => 'scorm_cumulative_session_time']);

        $cumulativeScormTime = 1;
        if ('false' === $result->getSelectedValue()) {
            $cumulativeScormTime = 0;
        }
        $this->addSql("UPDATE c_lp SET accumulate_scorm_time = $cumulativeScormTime");
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
    }
}
