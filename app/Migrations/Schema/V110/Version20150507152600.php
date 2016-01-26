<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150507152600
 *
 * @package Application\Migrations\Schema\V110
 */
class Version20150507152600 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        // Move some settings from configuration.php to the database
        // Current settings categories are:
        // Platform, Course, Session, Languages, User, Tools, Editor, Security,
        // Tuning, Gradebook, Timezones, Tracking, Search, stylesheets (lowercase),
        // LDAP, CAS, Shibboleth, Facebook

        // Setting $_configuration['hide_home_top_when_connected'] = true;
        $value = $this->getConfigurationValue('hide_home_top_when_connected');

        $this->addSettingCurrent(
            'hide_home_top_when_connected',
            '',
            'radio',
            'Platform',
            ($value ? 'true' : 'false'),
            'HideHomeTopContentWhenLoggedInText',
            'HideHomeTopContentWhenLoggedInComment',
            null,
            '',
            1,
            true,
            false,
            [
                0 => ['value' => 'true', 'text' => 'Yes'],
                1 => ['value' => 'false', 'text' => 'No'],
            ]
        );

        // Hide the global announcements for non-connected users
        //$_configuration['hide_global_announcements_when_not_connected'] = true;
        $value = $this->getConfigurationValue('hide_global_announcements_when_not_connected');
        $this->addSettingCurrent(
            'hide_global_announcements_when_not_connected',
            '',
            'radio',
            'Platform',
            ($value?'true':'false'),
            'HideGlobalAnnouncementsWhenNotLoggedInText',
            'HideGlobalAnnouncementsWhenNotLoggedInComment',
            null,
            '',
            1,
            true,
            false,
            [
                0 => ['value' => 'true', 'text' => 'Yes'],
                1 => ['value' => 'false', 'text' => 'No'],
            ]
        );

        // Use this course as template for all new courses (define course real ID as value)
        //$_configuration['course_creation_use_template'] = 14;
        $value = $this->getConfigurationValue('course_creation_use_template');
        $this->addSettingCurrent(
            'course_creation_use_template',
            '',
            'textfield',
            'Course',
            ($value?$value:''),
            'CourseCreationUsesTemplateText',
            'CourseCreationUsesTemplateComment',
            null,
            '',
            1,
            true,
            false,
            [
                0 => ['value' => 'true', 'text' => 'Yes'],
                1 => ['value' => 'false', 'text' => 'No'],
            ]
        );

        // Add password strength checker
        //$_configuration['allow_strength_pass_checker'] = true;
        $value = $this->getConfigurationValue('allow_strength_pass_checker');
        $this->addSettingCurrent(
            'allow_strength_pass_checker',
            '',
            'radio',
            'Security',
            ($value?'true':'false'),
            'EnablePasswordStrengthCheckerText',
            'EnablePasswordStrengthCheckerComment',
            null,
            '',
            1,
            true,
            false,
            [
                0 => ['value' => 'true', 'text' => 'Yes'],
                1 => ['value' => 'false', 'text' => 'No'],
            ]
        );

        // Enable captcha
        // $_configuration['allow_captcha'] = true;
        $value = $this->getConfigurationValue('allow_captcha');
        $this->addSettingCurrent(
            'allow_captcha',
            '',
            'radio',
            'Security',
            ($value?'true':'false'),
            'EnableCaptchaText',
            'EnableCaptchaComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );
        // Prevent account from logging in for a certain amount of time
        // if captcha is wrong for the specified number of times
        //$_configuration['captcha_number_mistakes_to_block_account'] = 5;
        $value = $this->getConfigurationValue('captcha_number_mistakes_to_block_account');
        $this->addSettingCurrent(
            'captcha_number_mistakes_to_block_account',
            '',
            'textfield',
            'Security',
            ($value?$value:5),
            'CaptchaNumberOfMistakesBeforeBlockingAccountText',
            'CaptchaNumberOfMistakesBeforeBlockingAccountComment',
            null,
            '',
            1,
            true,
            false
        );
        // Prevent account from logging in for the specified number of minutes
        //$_configuration['captcha_time_to_block'] = 5;//minutes
        $value = $this->getConfigurationValue('captcha_time_to_block');
        $this->addSettingCurrent(
            'captcha_time_to_block',
            '',
            'textfield',
            'Security',
            ($value?$value:5),
            'CaptchaTimeAccountIsLockedText',
            'CaptchaTimeAccountIsLockedComment',
            null,
            '',
            1,
            true,
            false
        );

        // Allow DRH role to access all content and users from the sessions he follows
        //$_configuration['drh_can_access_all_session_content'] = true;
        $value = $this->getConfigurationValue('drh_can_access_all_session_content');
        $this->addSettingCurrent(
            'drh_can_access_all_session_content',
            '',
            'radio',
            'Session',
            ($value?'true':'false'),
            'DRHAccessToAllSessionContentText',
            'DRHAccessToAllSessionContentComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Display group's forum in general forum tool
        //$_configuration['display_groups_forum_in_general_tool'] = true;
        $value = $this->getConfigurationValue('display_groups_forum_in_general_tool');
        $this->addSettingCurrent(
            'display_groups_forum_in_general_tool',
            '',
            'radio',
            'Tools',
            ($value?'true':'false'),
            'ShowGroupForaInGeneralToolText',
            'ShowGroupForaInGeneralToolComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Allow course tutors in sessions to add existing students to their session
        //$_configuration['allow_tutors_to_assign_students_to_session'] = 'false';
        $value = $this->getConfigurationValue('allow_tutors_to_assign_students_to_session');
        $this->addSettingCurrent(
            'allow_tutors_to_assign_students_to_session',
            '',
            'radio',
            'Session',
            ($value?'true':'false'),
            'TutorsCanAssignStudentsToSessionsText',
            'TutorsCanAssignStudentsToSessionsComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );
    }

    /**
     * We don't allow downgrades yet
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("
            DELETE FROM settings_options WHERE variable IN ('hide_home_top_when_connected', 'hide_global_announcements_when_not_connected', 'course_creation_use_template', 'allow_strength_pass_checker', 'allow_captcha', 'captcha_number_mistakes_to_block_account', 'captcha_time_to_block', 'drh_can_access_all_session_content', 'display_groups_forum_in_general_tool', 'allow_tutors_to_assign_students_to_session')
        ");
        $this->addSql("
            DELETE FROM settings_current WHERE variable IN ('hide_home_top_when_connected', 'hide_global_announcements_when_not_connected', 'course_creation_use_template', 'allow_strength_pass_checker', 'allow_captcha', 'captcha_number_mistakes_to_block_account', 'captcha_time_to_block', 'drh_can_access_all_session_content', 'display_groups_forum_in_general_tool', 'allow_tutors_to_assign_students_to_session')
        ");
    }
}
