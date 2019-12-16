<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150812230500.
 */
class Version20150812230500 extends AbstractMigrationChamilo
{
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
                ['value' => 'false', 'text' => 'No'],
            ]
        );
    }

    public function down(Schema $schema)
    {
        $entityManage = $this->getEntityManager();

        $deleteOptions = $entityManage->createQueryBuilder();

        $deleteOptions->delete('ChamiloCoreBundle:SettingsOptions', 'o')
            ->andWhere(
                $deleteOptions->expr()->in(
                    'o.variable',
                    [
                        'allow_coach_feedback_exercises',
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
                        'allow_coach_feedback_exercises',
                    ]
                )
            );
        $deleteSettings->getQuery()->execute();
    }
}
