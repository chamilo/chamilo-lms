<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Calendar color
 */
class Version20150813200000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $entityManage = $this->getEntityManager();

        $deleteOptions = $entityManage->createQueryBuilder();
        $deleteSettings = $entityManage->createQueryBuilder();

        $deleteOptions->delete('ChamiloCoreBundle:SettingsOptions', 'o')
            ->andWhere(
                $deleteOptions->expr()->in(
                    'o.variable',
                    [
                        'math_mimetex'
                    ]
                )
            );
        $deleteOptions->getQuery()->execute();

        $deleteSettings->delete('ChamiloCoreBundle:SettingsCurrent', 's')
            ->andWhere(
                $deleteSettings->expr()->in(
                    's.variable',
                    [
                        'math_mimetex'
                    ]
                )
            );
        $deleteSettings->getQuery()->execute();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSettingCurrent(
            'math_mimetex',
            null,
            'radio',
            'Editor',
            'false',
            'MathMimetexTitle',
            'MathMimetexComment',
            null,
            null,
            1,
            false,
            true,
            [
                0 => ['value' => 'true', 'text' => 'Yes'],
                1 => ['value' => 'false', 'text' => 'No']
            ]
        );
    }
}
