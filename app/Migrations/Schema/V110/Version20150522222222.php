<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150522222222
 * @package Application\Migrations\Schema\V11010
 */
class Version20150522222222 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // The first ALTER queries here requires a check because the field might already exist
        $connection = $this->connection;
        $fieldExists = false;
        $sql = "SELECT *
                FROM user
                LIMIT 1";
        $result = $connection->executeQuery($sql);
        $dataList = $result->fetchAll();
        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                if (isset($data['last_login'])) {
                    $fieldExists = true;
                }
            }
        }
        if (!$fieldExists) {
            $this->addSql('ALTER TABLE user ADD COLUMN last_login datetime DEFAULT NULL');
        }
        // calendar events comments
        $fieldExists = false;
        $sql = "SELECT *
                FROM c_calendar_event
                LIMIT 1";
        $result = $connection->executeQuery($sql);
        $dataList = $result->fetchAll();
        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                if (isset($data['comment'])) {
                    $fieldExists = true;
                }
            }
        }
        if (!$fieldExists) {
            $this->addSql("ALTER TABLE c_calendar_event ADD COLUMN comment TEXT");
        }

        // Move some settings from configuration.php to the database
        // Current settings categories are:
        // Platform, Course, Session, Languages, User, Tools, Editor, Security,
        // Tuning, Gradebook, Timezones, Tracking, Search, stylesheets (lowercase),
        // LDAP, CAS, Shibboleth, Facebook

        // Allow select the return link in the LP view
        $value = $this->getConfigurationValue('allow_lp_return_link');
        $this->addSettingCurrent(
            'allow_lp_return_link',
            '',
            'radio',
            'Course',
            ($value?$value:'true'),
            'AllowLearningPathReturnLinkTitle',
            'AllowLearningPathReturnLinkComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // If true the export link is blocked.
        $value = $this->getConfigurationValue('hide_scorm_export_link');
        $this->addSettingCurrent(
            'hide_scorm_export_link',
            '',
            'radio',
            'Course',
            ($value?$value:'false'),
            'HideScormExportLinkTitle',
            'HideScormExportLinkComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // If true the copy link is blocked.
        //$_configuration['hide_scorm_copy_link'] = false;
        $value = $this->getConfigurationValue('hide_scorm_copy_link');
        $this->addSettingCurrent(
            'hide_scorm_copy_link',
            '',
            'radio',
            'Course',
            ($value?$value:'false'),
            'HideScormCopyLinkTitle',
            'HideScormCopyLinkComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // If true the pdf export link is blocked.
        //$_configuration['hide_scorm_pdf_link'] = false;
        $value = $this->getConfigurationValue('hide_scorm_pdf_link');
        $this->addSettingCurrent(
            'hide_scorm_pdf_link',
            '',
            'radio',
            'Course',
            ($value?$value:'false'),
            'HideScormPdfLinkTitle',
            'HideScormPdfLinkComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Default session days before coach access
        //$_configuration['session_days_before_coach_access'] = 0;
        $value = $this->getConfigurationValue('session_days_before_coach_access');
        $this->addSettingCurrent(
            'session_days_before_coach_access',
            '',
            'textfield',
            'Session',
            ($value?$value:'0'),
            'SessionDaysBeforeCoachAccessTitle',
            'SessionDaysBeforeCoachAccessComment',
            null,
            '',
            1,
            true,
            false
        );


        // Default session days after coach access
        //$_configuration['session_days_after_coach_access'] = 0;
        $value = $this->getConfigurationValue('session_days_after_coach_access');
        $this->addSettingCurrent(
            'session_days_after_coach_access',
            '',
            'textfield',
            'Session',
            ($value?$value:'0'),
            'SessionDaysAfterCoachAccessTitle',
            'SessionDaysAfterCoachAccessComment',
            null,
            '',
            1,
            true,
            false
        );

        // PDF Logo header in app/Resources/public/css/themes/xxx/images/pdf_logo_header.png
        //$_configuration['pdf_logo_header'] = false;
        $value = $this->getConfigurationValue('pdf_logo_header');
        $this->addSettingCurrent(
            'pdf_logo_header',
            '',
            'radio',
            'Course',
            ($value?$value:'false'),
            'PdfLogoHeaderTitle',
            'PdfLogoHeaderComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Order inscription user list by official_code
        //$_configuration['order_user_list_by_official_code'] = false;
        $value = $this->getConfigurationValue('order_user_list_by_official_code');
        $this->addSettingCurrent(
            'order_user_list_by_official_code',
            '',
            'radio',
            'Platform',
            ($value?$value:'false'),
            'OrderUserListByOfficialCodeTitle',
            'OrderUserListByOfficialCodeComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Default course setting "email_alert_manager_on_new_quiz"
        //$_configuration['email_alert_manager_on_new_quiz'] = 1;
        $value = $this->getConfigurationValue('email_alert_manager_on_new_quiz');
        $this->addSettingCurrent(
            'email_alert_manager_on_new_quiz',
            '',
            'radio',
            'Course',
            ($value?$value:'true'),
            'AlertManagerOnNewQuizTitle',
            'AlertManagerOnNewQuizComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Show official code in exercise report list.
        //$_configuration['show_official_code_exercise_result_list'] = false;
        $value = $this->getConfigurationValue('show_official_code_exercise_result_list');
        $this->addSettingCurrent(
            'show_official_code_exercise_result_list',
            '',
            'radio',
            'Tools',
            ($value?$value:'false'),
            'ShowOfficialCodeInExerciseResultListTitle',
            'ShowOfficialCodeInExerciseResultListComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );


        // Hide private courses from course catalog
        //$_configuration['course_catalog_hide_private'] = false;
        $value = $this->getConfigurationValue('course_catalog_hide_private');
        $this->addSettingCurrent(
            'course_catalog_hide_private',
            '',
            'radio',
            'Platform',
            ($value?$value:'false'),
            'HidePrivateCoursesFromCourseCatalogTitle',
            'HidePrivateCoursesFromCourseCatalogComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Display sessions catalog
        // 0 = show only courses; 1 = show only sessions; 2 = show courses and sessions
        //$_configuration['catalog_show_courses_sessions'] = 0;
        $value = $this->getConfigurationValue('catalog_show_courses_sessions');
        $this->addSettingCurrent(
            'catalog_show_courses_sessions',
            '',
            'radio',
            'Platform',
            ($value?$value:'0'),
            'CoursesCatalogueShowSessionsTitle',
            'CoursesCatalogueShowSessionsComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => '0', 'text' => 'CatalogueShowOnlyCourses'], 1 => ['value' => '1', 'text' => 'CatalogueShowOnlySessions'], 2 => ['value' => '2', 'text' => 'CatalogueShowCoursesAndSessions']]
        );

        // Auto detect language custom pages.
        // $_configuration['auto_detect_language_custom_pages'] = true;
        $value = $this->getConfigurationValue('auto_detect_language_custom_pages');
        $this->addSettingCurrent(
            'auto_detect_language_custom_pages',
            '',
            'radio',
            'Platform',
            ($value?$value:'true'),
            'AutoDetectLanguageCustomPagesTitle',
            'AutoDetectLanguageCustomPagesComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Show reduce LP report
        //$_configuration['lp_show_reduced_report'] = false;
        $value = $this->getConfigurationValue('lp_show_reduced_report');
        $this->addSettingCurrent(
            'lp_show_reduced_report',
            '',
            'radio',
            'Tools',
            ($value?$value:'false'),
            'LearningPathShowReducedReportTitle',
            'LearningPathShowReducedReportComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        //Allow session-to-session copy
        //$_configuration['allow_session_course_copy_for_teachers'] = true;
        $value = $this->getConfigurationValue('allow_session_course_copy_for_teachers');
        $this->addSettingCurrent(
            'allow_session_course_copy_for_teachers',
            '',
            'radio',
            'Session',
            ($value?$value:'false'),
            'AllowSessionCourseCopyForTeachersTitle',
            'AllowSessionCourseCopyForTeachersComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Hide the logout button
        //$_configuration['hide_logout_button'] = true;
        $value = $this->getConfigurationValue('hide_logout_button');
        $this->addSettingCurrent(
            'hide_logout_button',
            '',
            'radio',
            'Security',
            ($value?$value:'false'),
            'HideLogoutButtonTitle',
            'HideLogoutButtonComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Prevent redirecting admin to admin page
        //$_configuration['redirect_admin_to_courses_list'] = true;
        $value = $this->getConfigurationValue('redirect_admin_to_courses_list');
        $this->addSettingCurrent(
            'redirect_admin_to_courses_list',
            '',
            'radio',
            'Platform',
            ($value?$value:'false'),
            'RedirectAdminToCoursesListTitle',
            'RedirectAdminToCoursesListComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Shows the custom course icon instead of the classic green board icon
        //$_configuration['course_images_in_courses_list'] = false;
        $value = $this->getConfigurationValue('course_images_in_courses_list');
        $this->addSettingCurrent(
            'course_images_in_courses_list',
            '',
            'radio',
            'Course',
            ($value?$value:'false'),
            'CourseImagesInCoursesListTitle',
            'CourseImagesInCoursesListComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Which student publication will be taken when connected to the gradebook: first|last
        //$_configuration['student_publication_to_take_in_gradebook'] = 'first';
        $value = $this->getConfigurationValue('student_publication_to_take_in_gradebook');
        $this->addSettingCurrent(
            'student_publication_to_take_in_gradebook',
            '',
            'radio',
            'Gradebook',
            ($value?$value:'first'),
            'StudentPublicationSelectionForGradebookTitle',
            'StudentPublicationSelectionForGradebookComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'first', 'text' => 'First'], 1 => ['value' => 'last', 'text' => 'Last']]
        );

        // Show a filter by official code
        //$_configuration['certificate_filter_by_official_code'] = false;
        $value = $this->getConfigurationValue('certificate_filter_by_official_code');
        $this->addSettingCurrent(
            'certificate_filter_by_official_code',
            '',
            'radio',
            'Gradebook',
            ($value?$value:'false'),
            'FilterCertificateByOfficialCodeTitle',
            'FilterCertificateByOfficialCodeComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Max quantity of fkceditor allowed in the exercise result page otherwise
        // Textareas are used.
        //$_configuration['exercise_max_ckeditors_in_page'] = 0;
        $value = $this->getConfigurationValue('exercise_max_ckeditors_in_page');
        $this->addSettingCurrent(
            'exercise_max_ckeditors_in_page',
            '',
            'textfield',
            'Tools',
            ($value?$value:'0'),
            'MaxCKeditorsOnExerciseResultsPageTitle',
            'MaxCKeditorsOnExerciseResultsPageComment',
            null,
            '',
            1,
            true,
            false,
            array()
        );

        // Default upload option
        //$_configuration['document_if_file_exists_option'] = 'rename'; // overwrite
        $value = $this->getConfigurationValue('document_if_file_exists_option');
        $this->addSettingCurrent(
            'document_if_file_exists_option',
            '',
            'radio',
            'Tools',
            ($value?$value:'rename'),
            'DocumentDefaultOptionIfFileExistsTitle',
            'DocumentDefaultOptionIfFileExistsComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'rename', 'text' => 'Rename'], 1 => ['value' => 'overwrite', 'text' => 'Overwrite']]
        );
        // Enable add_gradebook_certificates.php cron task
        //$_configuration['add_gradebook_certificates_cron_task_enabled'] = true;
        $value = $this->getConfigurationValue('add_gradebook_certificates_cron_task_enabled');
        $this->addSettingCurrent(
            'add_gradebook_certificates_cron_task_enabled',
            '',
            'radio',
            'Tools',
            ($value?$value:'false'),
            'GradebookCronTaskGenerationTitle',
            'GradebookCronTaskGenerationComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Which OpenBadges backpack send the badges
        //$_configuration['openbadges_backpack'] = 'https://backpack.openbadges.org/';
        $value = $this->getConfigurationValue('openbadges_backpack');
        $this->addSettingCurrent(
            'openbadges_backpack',
            '',
            'textfield',
            'Gradebook',
            ($value?$value:'https://backpack.openbadges.org/'),
            'OpenBadgesBackpackUrlTitle',
            'OpenBadgesBackpackUrlComment',
            null,
            '',
            1,
            true,
            false,
            []
        );

        // Shows a warning message explaining that the site uses cookies
        //$_configuration['cookie_warning'] = false;
        $value = $this->getConfigurationValue('cookie_warning');
        $this->addSettingCurrent(
            'cookie_warning',
            '',
            'radio',
            'Tools',
            ($value?$value:'false'),
            'CookieWarningTitle',
            'CookieWarningComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // If there are any tool available and the user is not registered hide the group
        //$_configuration['hide_course_group_if_no_tools_available'] = false;
        $value = $this->getConfigurationValue('hide_course_group_if_no_tools_available');
        $this->addSettingCurrent(
            'hide_course_group_if_no_tools_available',
            '',
            'radio',
            'Tools',
            ($value?$value:'false'),
            'HideCourseGroupIfNoToolAvailableTitle',
            'HideCourseGroupIfNoToolAvailableComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Allow student to enroll into a session without an approval needing
        //$_configuration['catalog_allow_session_auto_subscription'] = false;
        $value = $this->getConfigurationValue('catalog_allow_session_auto_subscription');
        $this->addSettingCurrent(
            'catalog_allow_session_auto_subscription',
            '',
            'radio',
            'Session',
            ($value?$value:'false'),
            'CatalogueAllowSessionAutoSubscriptionTitle',
            'CatalogueAllowSessionAutoSubscriptionComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Decode UTF-8 from Web Services (option passed to SOAP)
        //$_configuration['registration.soap.php.decode_utf8'] = false;
        $value = $this->getConfigurationValue('registration.soap.php.decode_utf8');
        $this->addSettingCurrent(
            'registration.soap.php.decode_utf8',
            '',
            'radio',
            'Platform',
            ($value?$value:'false'),
            'SoapRegistrationDecodeUtf8Title',
            'SoapRegistrationDecodeUtf8Comment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Show delete option in attendance
        //$_configuration['allow_delete_attendance'] = false;
        $value = $this->getConfigurationValue('allow_delete_attendance');
        $this->addSettingCurrent(
            'allow_delete_attendance',
            '',
            'radio',
            'Tools',
            ($value?$value:'false'),
            'AttendanceDeletionEnableTitle',
            'AttendanceDeletionEnableComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Enable Gravatar profile image if no local image has been given
        //$_configuration['gravatar_enabled'] = true;
        $value = $this->getConfigurationValue('gravatar_enabled');
        $this->addSettingCurrent(
            'gravatar_enabled',
            '',
            'radio',
            'Platform',
            ($value?$value:'false'),
            'GravatarPicturesTitle',
            'GravatarPicturesComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // If Gravatar is enabled, tells which type of picture we want (default is "mm").
        // Options: mm | identicon | monsterid | wavatar
        //$_configuration['gravatar_type'] = 'mm';
        $value = $this->getConfigurationValue('gravatar_type');
        $this->addSettingCurrent(
            'gravatar_type',
            '',
            'radio',
            'Platform',
            ($value?$value:'mm'),
            'GravatarPicturesTypeTitle',
            'GravatarPicturesTypeComment',
            null,
            '',
            1,
            true,
            false,
            [
                0 => ['value' => 'mm', 'text' => 'mystery-man'],
                1 => ['value' => 'identicon', 'text' => 'identicon'],
                2 => ['value' => 'monsterid', 'text' => 'monsterid'],
                3 => ['value' => 'wavatar', 'text' => 'wavatar']
            ]
        );

        // Limit for the Session Admin role. The administration page show only
        // User block -> Add user
        // Course Sessions block -> Training session list
        //$_configuration['limit_session_admin_role'] = false;
        $value = $this->getConfigurationValue('limit_session_admin_role');
        $this->addSettingCurrent(
            'limit_session_admin_role',
            '',
            'radio',
            'Session',
            ($value?'true':'false'),
            'SessionAdminPermissionsLimitTitle',
            'SessionAdminPermissionsLimitComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Show session description
        //$_configuration['show_session_description'] = false;
        $value = $this->getConfigurationValue('show_session_description');
        $this->addSettingCurrent(
            'show_session_description',
            '',
            'radio',
            'Session',
            ($value?$value:'false'),
            'ShowSessionDescriptionTitle',
            'ShowSessionDescriptionComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Hide only for students the link to export certificates to PDF
        //$_configuration['hide_certificate_export_link_students'] = false;
        $value = $this->getConfigurationValue('hide_certificate_export_link_students');
        $this->addSettingCurrent(
            'hide_certificate_export_link_students',
            '',
            'radio',
            'Gradebook',
            ($value?$value:'false'),
            'CertificateHideExportLinkStudentTitle',
            'CertificateHideExportLinkStudentComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Hide for all user roles the link to export certificates to PDF
        //$_configuration['hide_certificate_export_link'] = false;
        $value = $this->getConfigurationValue('hide_certificate_export_link');
        $this->addSettingCurrent(
            'hide_certificate_export_link',
            '',
            'radio',
            'Gradebook',
            ($value?$value:'false'),
            'CertificateHideExportLinkTitle',
            'CertificateHideExportLinkComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Hide session course coach in dropbox sent to user list
        //$_configuration['dropbox_hide_course_coach'] = false;
        $value = $this->getConfigurationValue('dropbox_hide_course_coach');
        $this->addSettingCurrent(
            'dropbox_hide_course_coach',
            '',
            'radio',
            'Tools',
            ($value ? $value : 'false'),
            'DropboxHideCourseCoachTitle',
            'DropboxHideCourseCoachComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        $value = $this->getConfigurationValue('dropbox_hide_general_coach');
        $this->addSettingCurrent(
            'dropbox_hide_general_coach',
            '',
            'radio',
            'Tools',
            ($value ? $value : 'false'),
            'DropboxHideGeneralCoachTitle',
            'DropboxHideGeneralCoachComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // If SSO is used, the redirection to the master server is forced.
        //$_configuration['force_sso_redirect'] = false;
        $value = $this->getConfigurationValue('force_sso_redirect');
        $this->addSettingCurrent(
            'sso_force_redirect',
            '',
            'radio',
            'Security',
            ($value?$value:'false'),
            'SSOForceRedirectTitle',
            'SSOForceRedirectComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );

        // Session course ordering in the the session view.
        // false = alphabetic order (default)
        // true = based in the session course list
        //$_configuration['session_course_ordering'] = false;
        $value = $this->getConfigurationValue('session_course_ordering');
        $this->addSettingCurrent(
            'session_course_ordering',
            '',
            'radio',
            'Session',
            ($value?$value:'false'),
            'SessionCourseOrderingTitle',
            'SessionCourseOrderingComment',
            null,
            '',
            1,
            true,
            false,
            [0 => ['value' => 'true', 'text' => 'Yes'], 1 => ['value' => 'false', 'text' => 'No']]
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM settings_options WHERE variable IN ('session_course_ordering', 'sso_force_redirect', 'dropbox_hide_course_coach', 'hide_certificate_export_link', 'hide_certificate_export_link_students', 'show_session_description', 'limit_session_admin_role', 'gravatar_type', 'gravatar_enabled', 'allow_delete_attendance', 'registration.soap.php.decode_utf8', 'catalog_allow_session_auto_subscription', 'hide_course_group_if_no_tools_available', 'cookie_warning', 'openbadges_backpack', 'add_gradebook_certificates_cron_task_enabled', 'document_if_file_exists_option', 'exercise_max_ckeditors_in_page', 'certificate_filter_by_official_code', 'student_publication_to_take_in_gradebook', 'course_images_in_courses_list', 'redirect_admin_to_courses_list', 'hide_logout_button', 'allow_session_course_copy_for_teachers', 'lp_show_reduced_report', 'auto_detect_language_custom_pages', 'catalog_show_courses_sessions', 'course_catalog_hide_private', 'show_official_code_exercise_result_list', 'allow_lp_return_link', 'hide_scorm_export_link', 'hide_scorm_copy_link', 'hide_scorm_pdf_link', 'session_days_before_coach_access', 'session_days_after_coach_access', 'pdf_logo_header', 'order_user_list_by_official_code', 'email_alert_manager_on_new_quiz')");
        $this->addSql("DELETE FROM settings_current WHERE variable IN ('session_course_ordering', 'sso_force_redirect', 'dropbox_hide_course_coach', 'hide_certificate_export_link', 'hide_certificate_export_link_students', 'show_session_description', 'limit_session_admin_role', 'gravatar_type', 'gravatar_enabled', 'allow_delete_attendance', 'registration.soap.php.decode_utf8', 'catalog_allow_session_auto_subscription', 'hide_course_group_if_no_tools_available', 'cookie_warning', 'openbadges_backpack', 'add_gradebook_certificates_cron_task_enabled', 'document_if_file_exists_option', 'exercise_max_ckeditors_in_page', 'certificate_filter_by_official_code', 'student_publication_to_take_in_gradebook', 'course_images_in_courses_list', 'redirect_admin_to_courses_list', 'hide_logout_button', 'allow_session_course_copy_for_teachers', 'lp_show_reduced_report', 'auto_detect_language_custom_pages', 'catalog_show_courses_sessions', 'course_catalog_hide_private', 'show_official_code_exercise_result_list', 'allow_lp_return_link', 'hide_scorm_export_link', 'hide_scorm_copy_link', 'hide_scorm_pdf_link', 'session_days_before_coach_access', 'session_days_after_coach_access', 'pdf_logo_header', 'order_user_list_by_official_code', 'email_alert_manager_on_new_quiz')");

        $this->addSql('ALTER TABLE user DROP COLUMN last_login');
    }
}
