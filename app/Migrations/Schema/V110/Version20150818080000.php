<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Chamilo\CoreBundle\Entity\SettingsOptions;

/**
 * Version20150818080000 Migration
 * Add a setting options for catalog_show_courses_sessions
 */
class Version20150818080000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $entityManager = $this->getEntityManager();

        $settingOption = new SettingsOptions();
        $settingOption
            ->setVariable('catalog_show_courses_sessions')
            ->setValue(3)
            ->setDisplayText('CatalogueShowNone');

        $entityManager->persist($settingOption);
        $entityManager->flush();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $entityManager = $this->getEntityManager();
        $settingOption = $entityManager
            ->getRepository('ChamiloCoreBundle:SettingsOptions')
            ->findOneBy([
                'variable' => 'catalog_show_courses_sessions',
                'value' => 3
            ]);

        if (!empty($settingOption)) {
            $entityManager->remove($settingOption);
            $entityManager->flush();
        }
    }

}
