<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Calendar color
 */
class Version20150813143000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSettingCurrent(
            'prevent_multiple_simultaneous_login',
            null,
            'radio',
            'Security',
            'false',
            'PreventMultipleSimultaneousLoginTitle',
            'PreventMultipleSimultaneousLoginComment',
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
        $this->addSettingCurrent(
            'gradebook_detailed_admin_view',
            null,
            'radio',
            'Gradebook',
            'false',
            'ShowAdditionalColumnsInStudentResultsPageTitle',
            'ShowAdditionalColumnsInStudentResultsPageComment',
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
        $this->addSettingCurrent(
            'course_catalog_published',
            null,
            'radio',
            'Course',
            'false',
            'CourseCatalogIsPublicTitle',
            'CourseCatalogIsPublicComment',
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
        $this->addSettingCurrent(
            'user_reset_password',
            null,
            'radio',
            'Security',
            'false',
            'ResetPasswordTokenTitle',
            'ResetPasswordTokenComment',
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
        $this->addSettingCurrent(
            'user_reset_password_token_limit',
            null,
            'textfield',
            'Security',
            '3600',
            'ResetPasswordTokenLimitTitle',
            'ResetPasswordTokenLimitComment',
            null,
            null,
            1,
            false,
            true
        );
        $this->addSettingCurrent(
            'my_courses_view_by_session',
            null,
            'radio',
            'Session',
            'false',
            'ViewMyCoursesListBySessionTitle',
            'ViewMyCoursesListBySessionComment',
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

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $entityManage = $this->getEntityManager();

        $deleteOptions = $entityManage->createQueryBuilder();
        $deleteSettings = $entityManage->createQueryBuilder();

        $deleteOptions->delete('ChamiloCoreBundle:SettingsOptions', 'o')
            ->andWhere(
                $deleteOptions->expr()->in(
                    'o.variable',
                    [
                        'prevent_multiple_simultaneous_login',
                        'gradebook_detailed_admin_view',
                        'course_catalog_published',
                        'user_reset_password',
                        'user_reset_password_token_limit',
                        'my_courses_view_by_session'
                    ]
                )
            );
        $deleteOptions->getQuery()->execute();

        $deleteSettings->delete('ChamiloCoreBundle:SettingsCurrent', 's')
            ->andWhere(
                $deleteSettings->expr()->in(
                    's.variable',
                    [
                        'prevent_multiple_simultaneous_login',
                        'gradebook_detailed_admin_view',
                        'course_catalog_published',
                        'user_reset_password',
                        'my_courses_view_by_session'
                    ]
                )
            );
        $deleteSettings->getQuery()->execute();
    }
}
