<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150821150000
 *
 * @package Application\Migrations\Schema\V11010
 */
class Version20150821150000 extends AbstractMigrationChamilo
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
                        'display_mini_month_calendar'
                    ]
                )
            );
        $deleteOptions->getQuery()->execute();

        $deleteSettings->delete('ChamiloCoreBundle:SettingsCurrent', 's')
            ->andWhere(
                $deleteSettings->expr()->in(
                    's.variable',
                    [
                        'display_mini_month_calendar'
                    ]
                )
            );
        $deleteSettings->getQuery()->execute();
        $deleteOptions->delete('ChamiloCoreBundle:SettingsOptions', 'o')
            ->andWhere(
                $deleteOptions->expr()->in(
                    'o.variable',
                    [
                        'display_upcoming_events'
                    ]
                )
            );
        $deleteOptions->getQuery()->execute();

        $deleteSettings->delete('ChamiloCoreBundle:SettingsCurrent', 's')
            ->andWhere(
                $deleteSettings->expr()->in(
                    's.variable',
                    [
                        'display_upcoming_events'
                    ]
                )
            );
        $deleteSettings->getQuery()->execute();
        $deleteSettings->delete('ChamiloCoreBundle:SettingsCurrent', 's')
            ->andWhere(
                $deleteSettings->expr()->in(
                    's.variable',
                    [
                        'number_of_upcoming_events'
                    ]
                )
            );
        $deleteSettings->getQuery()->execute();

        $deleteOptions->delete('ChamiloCoreBundle:SettingsOptions', 'o')
            ->andWhere(
                $deleteOptions->expr()->in(
                    'o.variable',
                    [
                        'allow_reservation'
                    ]
                )
            );
        $deleteOptions->getQuery()->execute();

        $deleteSettings->delete('ChamiloCoreBundle:SettingsCurrent', 's')
            ->andWhere(
                $deleteSettings->expr()->in(
                    's.variable',
                    [
                        'allow_reservation'
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
            'display_mini_month_calendar',
            null,
            'radio',
            'Tools',
            'true',
            'DisplayMiniMonthCalendarTitle',
            'DisplayMiniMonthCalendarComment',
            null,
            null,
            1,
            false,
            false,
            [
                0 => ['value' => 'true', 'text' => 'Yes'],
                1 => ['value' => 'false', 'text' => 'No']
            ]
        );
        $this->addSettingCurrent(
            'display_upcoming_events',
            null,
            'radio',
            'Tools',
            'true',
            'DisplayUpcomingEventsTitle',
            'DisplayUpcomingEventsComment',
            null,
            null,
            1,
            false,
            false,
            [
                0 => ['value' => 'true', 'text' => 'Yes'],
                1 => ['value' => 'false', 'text' => 'No']
            ]
        );
        $this->addSettingCurrent(
            'number_of_upcoming_events',
            null,
            'textfield',
            'Tools',
            '1',
            'NumberOfUpcomingEventsTitle',
            'NumberOfUpcomingEventsComment',
            null,
            null,
            1,
            false,
            false
        );
        $this->addSettingCurrent(
            'allow_reservation',
            null,
            'radio',
            'Tools',
            'false',
            'AllowReservationTitle',
            'AllowReservationComment',
            null,
            null,
            1,
            true,
            false,
            [
                0 => ['value' => 'true', 'text' => 'Yes'],
                1 => ['value' => 'false', 'text' => 'No']
            ]
        );
    }

}
