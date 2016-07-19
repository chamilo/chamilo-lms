<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150812230500
 *
 * @package Application\Migrations\Schema\V11010
 */
class Version20150812230500 extends AbstractMigrationChamilo
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSettingCurrent(
            'allow_coach_feedback_exercises',
            null,
            'radio',
            'Session',
            'false',
            'AllowCoachFeedbackExercisesTitle',
            'AllowCoachFeedbackExercisesComment',
            null,
            null,
            1,
            true,
            false,
            [
                ['value' => 'true', 'text' => 'Yes'],
                ['value' => 'false', 'text' => 'No']
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $entityManage = $this->getEntityManager();

        $deleteOptions = $entityManage->createQueryBuilder();

        $deleteOptions->delete('ChamiloCoreBundle:SettingsOptions', 'o')
            ->andWhere(
                $deleteOptions->expr()->in(
                    'o.variable',
                    [
                        'allow_coach_feedback_exercises'
                    ]
                )
            );
        $deleteOptions->getQuery()->execute();

        $deleteSettings = $entityManage->createQueryBuilder();
        $deleteSettings->delete('ChamiloCoreBundle:SettingsCurrent', 's')
            ->andWhere(
                $deleteSettings->expr()->in(
                    's.variable',
                    [
                        'allow_coach_feedback_exercises'
                    ]
                )
            );
        $deleteSettings->getQuery()->execute();
    }

}
