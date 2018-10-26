<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\SettingsBundle\Manager;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\ResourceBundle\Controller\EventDispatcherInterface;
use Sylius\Bundle\SettingsBundle\Event\SettingsEvent;
use Sylius\Bundle\SettingsBundle\Manager\SettingsManagerInterface;
use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Bundle\SettingsBundle\Model\SettingsInterface;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Class SettingsManager.
 *
 * @package Chamilo\SettingsBundle\Manager
 */
class SettingsManager implements SettingsManagerInterface
{
    protected $url;

    /**
     * @var ServiceRegistryInterface
     */
    protected $schemaRegistry;

    /**
     * @var ServiceRegistryInterface
     */
    protected $resolverRegistry;

    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var FactoryInterface
     */
    protected $settingsFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Runtime cache for resolved parameters.
     *
     * @var Settings[]
     */
    protected $resolvedSettings = [];

    /**
     * SettingsManager constructor.
     *
     * @param ServiceRegistryInterface $schemaRegistry
     * @param ServiceRegistryInterface $resolverRegistry
     * @param EntityManager            $manager
     * @param EntityRepository         $repository
     * @param FactoryInterface         $settingsFactory
     * @param $eventDispatcher
     */
    public function __construct(
        ServiceRegistryInterface $schemaRegistry,
        ServiceRegistryInterface $resolverRegistry,
        EntityManager $manager,
        EntityRepository $repository,
        FactoryInterface $settingsFactory,
        $eventDispatcher
    ) {
        $this->schemaRegistry = $schemaRegistry;
        $this->resolverRegistry = $resolverRegistry;
        $this->manager = $manager;
        $this->repository = $repository;
        $this->settingsFactory = $settingsFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return AccessUrl
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param AccessUrl $url
     */
    public function setUrl(AccessUrl $url)
    {
        $this->url = $url;
    }

    /**
     * @param AccessUrl $url
     */
    public function updateSchemas(AccessUrl $url)
    {
        $this->url = $url;
        $schemas = array_keys($this->getSchemas());

        /**
         * @var string
         * @var \Sylius\Bundle\SettingsBundle\Schema\SchemaInterface $schema
         */
        foreach ($schemas as $schema) {
            $settings = $this->load($this->convertServiceToNameSpace($schema));
            $this->update($settings);
        }
    }

    /**
     * @param AccessUrl $url
     */
    public function installSchemas(AccessUrl $url)
    {
        $this->url = $url;
        $schemas = array_keys($this->getSchemas());

        /**
         * @var string
         * @var \Sylius\Bundle\SettingsBundle\Schema\SchemaInterface $schema
         */
        foreach ($schemas as $schema) {
            $settings = $this->load($this->convertServiceToNameSpace($schema));
            $this->save($settings);
        }
    }

    /**
     * @return array
     */
    public function getSchemas()
    {
        return $this->schemaRegistry->all();
    }

    /**
     * Get variables and categories as in 1.11.x.
     *
     * @return array
     */
    public function getVariablesAndCategories()
    {
        $oldItems = [
            'Institution' => 'Platform',
            'InstitutionUrl' => 'Platform',
            'siteName' => 'Platform',
            'emailAdministrator' => 'admin', //'emailAdministrator' => 'Platform',
            'administratorSurname' => 'admin',
            'administratorTelephone' => 'admin',
            'administratorName' => 'admin',
            'show_administrator_data' => 'Platform',
            'show_tutor_data' => 'Session',
            'show_teacher_data' => 'Platform',
            'homepage_view' => 'Course',
            'show_toolshortcuts' => 'Course',
            'allow_group_categories' => 'Course',
            'server_type' => 'Platform',
            'platformLanguage' => 'Language',
            'showonline' => 'Platform',
            'profile' => 'User',
            'default_document_quotum' => 'Course',
            'registration' => 'User',
            'default_group_quotum' => 'Course',
            'allow_registration' => 'Platform',
            'allow_registration_as_teacher' => 'Platform',
            'allow_lostpassword' => 'Platform',
            'allow_user_headings' => 'Course',
            'allow_personal_agenda' => 'agenda',
            'display_coursecode_in_courselist' => 'Platform',
            'display_teacher_in_courselist' => 'Platform',
            'permanently_remove_deleted_files' => 'Tools',
            'dropbox_allow_overwrite' => 'Tools',
            'dropbox_max_filesize' => 'Tools',
            'dropbox_allow_just_upload' => 'Tools',
            'dropbox_allow_student_to_student' => 'Tools',
            'dropbox_allow_group' => 'Tools',
            'dropbox_allow_mailing' => 'Tools',
            'extended_profile' => 'User',
            'student_view_enabled' => 'Platform',
            'show_navigation_menu' => 'Course',
            'enable_tool_introduction' => 'course',
            'page_after_login' => 'Platform',
            'time_limit_whosonline' => 'Platform',
            'breadcrumbs_course_homepage' => 'Course',
            'example_material_course_creation' => 'Platform',
            'account_valid_duration' => 'Platform',
            'use_session_mode' => 'Session',
            'allow_email_editor' => 'Tools',
            //'registered' => null',
            //'donotlistcampus' =>'null',
            'show_email_addresses' => 'Platform',
            'service_ppt2lp' => 'NULL',
            'stylesheets' => 'stylesheets',
            'upload_extensions_list_type' => 'Security',
            'upload_extensions_blacklist' => 'Security',
            'upload_extensions_whitelist' => 'Security',
            'upload_extensions_skip' => 'Security',
            'upload_extensions_replace_by' => 'Security',
            'show_number_of_courses' => 'Platform',
            'show_empty_course_categories' => 'Platform',
            'show_back_link_on_top_of_tree' => 'Platform',
            'show_different_course_language' => 'Platform',
            'split_users_upload_directory' => 'Tuning',
            'hide_dltt_markup' => 'Languages',
            'display_categories_on_homepage' => 'Platform',
            'permissions_for_new_directories' => 'Security',
            'permissions_for_new_files' => 'Security',
            'show_tabs' => 'Platform',
            'default_forum_view' => 'Course',
            'platform_charset' => 'Languages',
            'noreply_email_address' => 'Platform',
            'survey_email_sender_noreply' => 'Course',
            'gradebook_enable' => 'Gradebook',
            'gradebook_score_display_coloring' => 'Gradebook',
            'gradebook_score_display_custom' => 'Gradebook',
            'gradebook_score_display_colorsplit' => 'Gradebook',
            'gradebook_score_display_upperlimit' => 'Gradebook',
            'gradebook_number_decimals' => 'Gradebook',
            'user_selected_theme' => 'Platform',
            'allow_course_theme' => 'Course',
            'show_closed_courses' => 'Platform',
            'extendedprofile_registration' => 'User',
            'extendedprofile_registrationrequired' => 'User',
            'add_users_by_coach' => 'Session',
            'extend_rights_for_coach' => 'Security',
            'extend_rights_for_coach_on_survey' => 'Security',
            'course_create_active_tools' => 'Tools',
            'show_session_coach' => 'Session',
            'allow_users_to_create_courses' => 'Platform',
            'allow_message_tool' => 'Tools',
            'allow_social_tool' => 'Tools',
            'allow_students_to_browse_courses' => 'Platform',
            'show_session_data' => 'Session',
            'allow_use_sub_language' => 'language',
            'show_glossary_in_documents' => 'Course',
            'allow_terms_conditions' => 'Platform',
            'search_enabled' => 'Search',
            'search_prefilter_prefix' => 'Search',
            'search_show_unlinked_results' => 'Search',
            'show_courses_descriptions_in_catalog' => 'Course',
            'allow_coach_to_edit_course_session' => 'Session',
            'show_glossary_in_extra_tools' => 'Course',
            'send_email_to_admin_when_create_course' => 'Platform',
            'go_to_course_after_login' => 'Course',
            'math_asciimathML' => 'Editor',
            'enabled_asciisvg' => 'Editor',
            'include_asciimathml_script' => 'Editor',
            'youtube_for_students' => 'Editor',
            'block_copy_paste_for_students' => 'Editor',
            'more_buttons_maximized_mode' => 'Editor',
            'students_download_folders' => 'Document',
            'users_copy_files' => 'Tools',
            'allow_students_to_create_groups_in_social' => 'Tools',
            'allow_send_message_to_all_platform_users' => 'Message',
            'message_max_upload_filesize' => 'Tools',
            'use_users_timezone' => 'profile', //'use_users_timezone' => 'Timezones',
            'timezone_value' => 'platform', //'timezone_value' => 'Timezones',
            'allow_user_course_subscription_by_course_admin' => 'Security',
            'show_link_bug_notification' => 'Platform',
            'show_link_ticket_notification' => 'Platform',
            'course_validation' => 'course', //'course_validation' => 'Platform',
            'course_validation_terms_and_conditions_url' => 'Platform',
            'enabled_wiris' => 'Editor',
            'allow_spellcheck' => 'Editor',
            'force_wiki_paste_as_plain_text' => 'Editor',
            'enabled_googlemaps' => 'Editor',
            'enabled_imgmap' => 'Editor',
            'enabled_support_svg' => 'Tools',
            'pdf_export_watermark_enable' => 'Platform',
            'pdf_export_watermark_by_course' => 'Platform',
            'pdf_export_watermark_text' => 'Platform',
            'enabled_insertHtml' => 'Editor',
            'students_export2pdf' => 'Document',
            'exercise_min_score' => 'Course',
            'exercise_max_score' => 'Course',
            'show_users_folders' => 'Tools',
            'show_default_folders' => 'Tools',
            'show_chat_folder' => 'Tools',
            'enabled_text2audio' => 'Tools',
            'course_hide_tools' => 'Course',
            'enabled_support_pixlr' => 'Tools',
            'show_groups_to_users' => 'Session',
            'accessibility_font_resize' => 'Platform',
            'hide_courses_in_sessions' => 'Session',
            'enable_quiz_scenario' => 'Course',
            'filter_terms' => 'Security',
            'header_extra_content' => 'Tracking',
            'footer_extra_content' => 'Tracking',
            'show_documents_preview' => 'Tools',
            'htmlpurifier_wiki' => 'Editor',
            'cas_activate' => 'CAS',
            'cas_server' => 'CAS',
            'cas_server_uri' => 'CAS',
            'cas_port' => 'CAS',
            'cas_protocol' => 'CAS',
            'cas_add_user_activate' => 'CAS',
            'update_user_info_cas_with_ldap' => 'CAS',
            'student_page_after_login' => 'Platform',
            'teacher_page_after_login' => 'Platform',
            'drh_page_after_login' => 'Platform',
            'sessionadmin_page_after_login' => 'Session',
            'student_autosubscribe' => 'Platform',
            'teacher_autosubscribe' => 'Platform',
            'drh_autosubscribe' => 'Platform',
            'sessionadmin_autosubscribe' => 'Session',
            'scorm_cumulative_session_time' => 'Course',
            'allow_hr_skills_management' => 'Gradebook',
            'enable_help_link' => 'Platform',
            'teachers_can_change_score_settings' => 'Gradebook',
            'allow_users_to_change_email_with_no_password' => 'User',
            'show_admin_toolbar' => 'display',
            'allow_global_chat' => 'Platform',
            'languagePriority1' => 'language',
            'languagePriority2' => 'language',
            'languagePriority3' => 'language',
            'languagePriority4' => 'language',
            'login_is_email' => 'Platform',
            'courses_default_creation_visibility' => 'Course',
            'gradebook_enable_grade_model' => 'Gradebook',
            'teachers_can_change_grade_model_settings' => 'Gradebook',
            'gradebook_default_weight' => 'Gradebook',
            'ldap_description' => 'LDAP',
            'shibboleth_description' => 'Shibboleth',
            'facebook_description' => 'Facebook',
            'gradebook_locking_enabled' => 'Gradebook',
            'gradebook_default_grade_model_id' => 'Gradebook',
            'allow_session_admins_to_manage_all_sessions' => 'Session',
            'allow_skills_tool' => 'Platform',
            'allow_public_certificates' => 'Course',
            'platform_unsubscribe_allowed' => 'Platform',
            'enable_iframe_inclusion' => 'Editor',
            'show_hot_courses' => 'Platform',
            'enable_webcam_clip' => 'Tools',
            'use_custom_pages' => 'Platform',
            'tool_visible_by_default_at_creation' => 'Tools',
            'prevent_session_admins_to_manage_all_users' => 'Session',
            'documents_default_visibility_defined_in_course' => 'Tools',
            'enabled_mathjax' => 'Editor',
            'meta_twitter_site' => 'Tracking',
            'meta_twitter_creator' => 'Tracking',
            'meta_title' => 'Tracking',
            'meta_description' => 'Tracking',
            'meta_image_path' => 'Tracking',
            'allow_teachers_to_create_sessions' => 'Session',
            'institution_address' => 'Platform',
            'chamilo_database_version' => 'null',
            'cron_remind_course_finished_activate' => 'Crons',
            'cron_remind_course_expiration_frequency' => 'Crons',
            'cron_remind_course_expiration_activate' => 'Crons',
            'allow_coach_feedback_exercises' => 'Session',
            'allow_my_files' => 'Platform',
            'ticket_allow_student_add' => 'Ticket',
            'ticket_send_warning_to_all_admins' => 'Ticket',
            'ticket_warn_admin_no_user_in_category' => 'Ticket',
            'ticket_allow_category_edition' => 'Ticket',
            'load_term_conditions_section' => 'Platform',
            'show_terms_if_profile_completed' => 'Ticket',
            'hide_home_top_when_connected' => 'Platform',
            'hide_global_announcements_when_not_connected' => 'Platform',
            'course_creation_use_template' => 'Course',
            'allow_strength_pass_checker' => 'Security',
            'allow_captcha' => 'Security',
            'captcha_number_mistakes_to_block_account' => 'Security',
            'captcha_time_to_block' => 'Security',
            'drh_can_access_all_session_content' => 'Session',
            'display_groups_forum_in_general_tool' => 'Tools',
            'allow_tutors_to_assign_students_to_session' => 'Session',
            'allow_lp_return_link' => 'Course',
            'hide_scorm_export_link' => 'Course',
            'hide_scorm_copy_link' => 'Course',
            'hide_scorm_pdf_link' => 'Course',
            'session_days_before_coach_access' => 'Session',
            'session_days_after_coach_access' => 'Session',
            'pdf_logo_header' => 'Course',
            'order_user_list_by_official_code' => 'Platform',
            'email_alert_manager_on_new_quiz' => 'exercise',
            'show_official_code_exercise_result_list' => 'Tools',
            'course_catalog_hide_private' => 'Platform',
            'catalog_show_courses_sessions' => 'Platform',
            'auto_detect_language_custom_pages' => 'Platform',
            'lp_show_reduced_report' => 'Course',
            'allow_session_course_copy_for_teachers' => 'Session',
            'hide_logout_button' => 'Platform',
            'redirect_admin_to_courses_list' => 'Platform',
            'course_images_in_courses_list' => 'Course',
            'student_publication_to_take_in_gradebook' => 'Gradebook',
            'certificate_filter_by_official_code' => 'Gradebook',
            'exercise_max_ckeditors_in_page' => 'Tools',
            'document_if_file_exists_option' => 'Tools',
            'add_gradebook_certificates_cron_task_enabled' => 'Gradebook',
            'openbadges_backpack' => 'Gradebook',
            'cookie_warning' => 'Tools',
            'hide_course_group_if_no_tools_available' => 'Tools',
            'catalog_allow_session_auto_subscription' => 'Session',
            'registration.soap.php.decode_utf8' => 'Platform',
            'allow_delete_attendance' => 'Tools',
            'gravatar_enabled' => 'Platform',
            'gravatar_type' => 'Platform',
            'limit_session_admin_role' => 'Session',
            'show_session_description' => 'Session',
            'hide_certificate_export_link_students' => 'Gradebook',
            'hide_certificate_export_link' => 'Gradebook',
            'dropbox_hide_course_coach' => 'Tools',
            'dropbox_hide_general_coach' => 'Tools',
            'session_course_ordering' => 'Session',
            'gamification_mode' => 'Platform',
            'prevent_multiple_simultaneous_login' => 'Security',
            'gradebook_detailed_admin_view' => 'Gradebook',
            'course_catalog_published' => 'Course',
            'user_reset_password' => 'Security',
            'user_reset_password_token_limit' => 'Security',
            'my_courses_view_by_session' => 'Session',
            'show_full_skill_name_on_skill_wheel' => 'Platform',
            'messaging_allow_send_push_notification' => 'WebServices',
            'messaging_gdc_project_number' => 'WebServices',
            'messaging_gdc_api_key' => 'WebServices',
            'teacher_can_select_course_template' => 'Course',
            'enable_record_audio' => 'Tools',
            'allow_show_skype_account' => 'Platform',
            'allow_show_linkedin_url' => 'Platform',
            'enable_profile_user_address_geolocalization' => 'User',
            'show_official_code_whoisonline' => 'Profile',
            'icons_mode_svg' => 'display',
            'user_name_order' => 'display',
            'user_name_sort_by' => 'display',
            'default_calendar_view' => 'agenda',
            'exercise_invisible_in_session' => 'exercise',
            'configure_exercise_visibility_in_course' => 'exercise',
            'allow_download_documents_by_api_key' => 'Webservices',
            'ProfilingFilterAddingUsers' => 'profile',
            'donotlistcampus' => 'platform',
            'gradebook_show_percentage_in_reports' => 'gradebook',
            'course_creation_splash_screen' => 'Course',
        ];

        return $oldItems;
    }

    /**
     * Rename old variable with variable used in Chamilo 2.0.
     *
     * @param string $variable
     *
     * @return mixed
     */
    public function renameVariable($variable)
    {
        $list = [
            'timezone_value' => 'timezone',
            'Institution' => 'institution',
            'SiteName' => 'site_name',
            'siteName' => 'site_name',
            'InstitutionUrl' => 'institution_url',
            'registration' => 'required_profile_fields',
            'stylesheets' => 'theme',
            'platformLanguage' => 'platform_language',
            'languagePriority1' => 'language_priority_1',
            'languagePriority2' => 'language_priority_2',
            'languagePriority3' => 'language_priority_3',
            'languagePriority4' => 'language_priority_4',
            'gradebook_score_display_coloring' => 'my_display_coloring',
            'document_if_file_exists_option' => 'if_file_exists_option',
            'ProfilingFilterAddingUsers' => 'profiling_filter_adding_users',
            'course_create_active_tools' => 'active_tools_on_create',
            'emailAdministrator' => 'administrator_email',
            'administratorSurname' => 'administrator_surname',
            'administratorName' => 'administrator_name',
            'administratorTelephone' => 'administrator_phone',
            'registration.soap.php.decode_utf8' => 'decode_utf8',
            'profile' => 'changeable_options',
        ];

        return isset($list[$variable]) ? $list[$variable] : $variable;
    }

    /**
     * Replace old Chamilo 1.x category with 2.0 version.
     *
     * @param string $variable
     * @param string $defaultCategory
     *
     * @return mixed
     */
    public function fixCategory($variable, $defaultCategory)
    {
        $settings = [
            'cookie_warning' => 'platform',
            'donotlistcampus' => 'platform',
            'administrator_email' => 'admin',
            'administrator_surname' => 'admin',
            'administrator_name' => 'admin',
            'administrator_phone' => 'admin',
            'exercise_max_ckeditors_in_page' => 'exercise',
            'allow_hr_skills_management' => 'skill',
            'accessibility_font_resize' => 'display',
            'account_valid_duration' => 'profile',
            'allow_global_chat' => 'chat',
            'allow_lostpassword' => 'registration',
            'allow_registration' => 'registration',
            'allow_registration_as_teacher' => 'registration',
            'required_profile_fields' => 'registration',
            'allow_skills_tool' => 'skill',
            'allow_students_to_browse_courses' => 'display',
            'allow_terms_conditions' => 'registration',
            'allow_users_to_create_courses' => 'course',
            'auto_detect_language_custom_pages' => 'language',
            'platform_language' => 'language',
            'course_validation' => 'course',
            'course_validation_terms_and_conditions_url' => 'course',
            'display_categories_on_homepage' => 'display',
            'display_coursecode_in_courselist' => 'course',
            'display_teacher_in_courselist' => 'course',
            'drh_autosubscribe' => 'registration',
            'drh_page_after_login' => 'registration',
            'enable_help_link' => 'display',
            'example_material_course_creation' => 'course',
            'login_is_email' => 'profile',
            'noreply_email_address' => 'mail',
            'page_after_login' => 'registration',
            'pdf_export_watermark_by_course' => 'document',
            'pdf_export_watermark_enable' => 'document',
            'pdf_export_watermark_text' => 'document',
            'platform_unsubscribe_allowed' => 'registration',
            'send_email_to_admin_when_create_course' => 'course',
            'show_admin_toolbar' => 'display',
            'show_administrator_data' => 'display',
            'show_back_link_on_top_of_tree' => 'display',
            'show_closed_courses' => 'display',
            'show_different_course_language' => 'display',
            'show_email_addresses' => 'display',
            'show_empty_course_categories' => 'display',
            'show_full_skill_name_on_skill_wheel' => 'skill',
            'show_hot_courses' => 'display',
            'show_link_bug_notification' => 'display',
            'show_number_of_courses' => 'display',
            'show_teacher_data' => 'display',
            'showonline' => 'display',
            'student_autosubscribe' => 'registration',
            'student_page_after_login' => 'registration',
            'student_view_enabled' => 'course',
            'teacher_autosubscribe' => 'registration',
            'teacher_page_after_login' => 'registration',
            'time_limit_whosonline' => 'display',
            'user_selected_theme' => 'profile',
            'hide_global_announcements_when_not_connected' => 'announcement',
            'hide_home_top_when_connected' => 'display',
            'hide_logout_button' => 'display',
            'institution_address' => 'platform',
            'redirect_admin_to_courses_list' => 'admin',
            'use_custom_pages' => 'platform',
            'allow_group_categories' => 'group',
            'allow_user_headings' => 'display',
            'default_document_quotum' => 'document',
            'default_forum_view' => 'forum',
            'default_group_quotum' => 'document',
            'enable_quiz_scenario' => 'exercise',
            'exercise_max_score' => 'exercise',
            'exercise_min_score' => 'exercise',
            'pdf_logo_header' => 'platform',
            'show_glossary_in_documents' => 'document',
            'show_glossary_in_extra_tools' => 'glossary',
            'survey_email_sender_noreply' => 'survey',
            'allow_coach_feedback_exercises' => 'exercise',
            'sessionadmin_autosubscribe' => 'registration',
            'sessionadmin_page_after_login' => 'registration',
            'show_tutor_data' => 'display',
            'allow_social_tool' => 'social',
            'allow_message_tool' => 'message',
            'allow_email_editor' => 'editor',
            'show_link_ticket_notification' => 'display',
            'permissions_for_new_directories' => 'document',
            'enable_profile_user_address_geolocalization' => 'profile',
            'allow_show_skype_account' => 'profile',
            'allow_show_linkedin_url' => 'profile',
            'allow_students_to_create_groups_in_social' => 'social',
            'default_calendar_view' => 'agenda',
            'documents_default_visibility_defined_in_course' => 'document',
            'message_max_upload_filesize' => 'message',
            'course_create_active_tools' => 'course',
            'tool_visible_by_default_at_creation' => 'document',
            'show_users_folders' => 'document',
            'show_default_folders' => 'document',
            'show_chat_folder' => 'chat',
            'enabled_support_svg' => 'editor',
            'enabled_support_pixlr' => 'editor',
            'enable_webcam_clip' => 'document',
            'enable_record_audio' => 'course',
            'enabled_text2audio' => 'document',
            'permanently_remove_deleted_files' => 'document',
            'allow_delete_attendance' => 'attendance',
            'display_groups_forum_in_general_tool' => 'forum',
            'dropbox_allow_overwrite' => 'dropbox',
            'allow_user_course_subscription_by_course_admin' => 'course',
            'hide_course_group_if_no_tools_available' => 'group',
            'extend_rights_for_coach_on_survey' => 'survey',
            'show_official_code_exercise_result_list' => 'exercise',
            'dropbox_max_filesize' => 'dropbox',
            'dropbox_allow_just_upload' => 'dropbox',
            'dropbox_allow_student_to_student' => 'dropbox',
            'dropbox_allow_group' => 'dropbox',
            'dropbox_allow_mailing' => 'dropbox',
            'upload_extensions_list_type' => 'document',
            'upload_extensions_blacklist' => 'document',
            'upload_extensions_skip' => 'document',
            'changeable_options' => 'profile',
            'users_copy_files' => 'document',
            'if_file_exists_option' => 'document',
            'permissions_for_new_files' => 'document',
            'extended_profile' => 'profile',
            'split_users_upload_directory' => 'profile',
            'show_documents_preview' => 'document',
            'decode_utf8' => 'webservice',
            'messaging_allow_send_push_notification' => 'webservice',
            'messaging_gdc_project_number' => 'webservice',
            'messaging_gdc_api_key' => 'webservice',
            'allow_download_documents_by_api_key' => 'webservice',
            'profiling_filter_adding_users' => 'profile',
            'hide_dltt_markup' => 'language',
            'active_tools_on_create' => 'course',
        ];

        return isset($settings[$variable]) ? $settings[$variable] : $defaultCategory;
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function getSetting($name)
    {
        if (false === strpos($name, '.')) {
            //throw new \InvalidArgumentException(sprintf('Parameter must be in format "namespace.name", "%s" given.', $name));

            // This code allows the possibility of calling
            // api_get_setting('allow_skills_tool') instead of
            // the "correct" way api_get_setting('platform.allow_skills_tool')
            $items = $this->getVariablesAndCategories();

            if (isset($items[$name])) {
                $originalName = $name;
                $name = $this->renameVariable($name);
                $category = $this->fixCategory(
                    strtolower($name),
                    strtolower($items[$originalName])
                );
                $name = $category.'.'.$name;
            } else {
                throw new \InvalidArgumentException(sprintf('Parameter must be in format "category.name", "%s" given.', $name));
            }
        }

        list($category, $name) = explode('.', $name);
        $settings = $this->load($category, $name);

        if (!$settings) {
            throw new \InvalidArgumentException(sprintf("Parameter '$name' not found in category '$category'"));
        }

        return $settings->get($name);
    }

    /**
     * @param string $category
     *
     * @return string
     */
    public function convertNameSpaceToService($category)
    {
        return 'chamilo_core.settings.'.$category;
    }

    /**
     * @param string $category
     *
     * @return string
     */
    public function convertServiceToNameSpace($category)
    {
        return str_replace('chamilo_core.settings.', '', $category);
    }

    /**
     * {@inheritdoc}
     */
    public function load($schemaAlias, $namespace = null, $ignoreUnknown = true)
    {
        $schemaAliasNoPrefix = $schemaAlias;
        $schemaAlias = 'chamilo_core.settings.'.$schemaAlias;

        if ($this->schemaRegistry->has($schemaAlias)) {
            /** @var SchemaInterface $schema */
            $schema = $this->schemaRegistry->get($schemaAlias);
        } else {
            return [];
        }

        /** @var \Sylius\Bundle\SettingsBundle\Model\Settings $settings */
        $settings = $this->settingsFactory->createNew();
        $settings->setSchemaAlias($schemaAlias);

        // We need to get a plain parameters array since we use the options resolver on it
        $parameters = $this->getParameters($schemaAliasNoPrefix);
        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);

        // Remove unknown settings' parameters (e.g. From a previous version of the settings schema)
        if (true === $ignoreUnknown) {
            foreach ($parameters as $name => $value) {
                if (!$settingsBuilder->isDefined($name)) {
                    unset($parameters[$name]);
                }
            }
        }

        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (array_key_exists($parameter, $parameters)) {
                if ($parameter === 'course_creation_use_template') {
                    if (empty($parameters[$parameter])) {
                        $parameters[$parameter] = null;
                    }
                } else {
                    $parameters[$parameter] = $transformer->reverseTransform($parameters[$parameter]);
                }
            }
        }

        $parameters = $settingsBuilder->resolve($parameters);
        $settings->setParameters($parameters);

        return $settings;
    }

    /**
     * @param SettingsInterface $settings
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(SettingsInterface $settings)
    {
        $namespace = $settings->getSchemaAlias();

        /** @var SchemaInterface $schema */
        $schema = $this->schemaRegistry->get($settings->getSchemaAlias());

        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);
        $parameters = $settingsBuilder->resolve($settings->getParameters());
        // Transform value. Example array to string using transformer. Example:
        // 1. Setting "tool_visible_by_default_at_creation" it's a multiple select
        // 2. Is defined as an array in class DocumentSettingsSchema
        // 3. Add transformer for that variable "ArrayToIdentifierTransformer"
        // 4. Here we recover the transformer and convert the array to string
        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $transformer->transform($parameters[$parameter]);
            }
        }
        $settings->setParameters($parameters);
        $persistedParameters = $this->repository->findBy(
            ['category' => $this->convertServiceToNameSpace($settings->getSchemaAlias())]
        );

        $persistedParametersMap = [];
        /** @var SettingsCurrent $parameter */
        foreach ($persistedParameters as $parameter) {
            $persistedParametersMap[$parameter->getVariable()] = $parameter;
        }

        /** @var SettingsCurrent $url */
        $url = $this->getUrl();
        $simpleCategoryName = str_replace('chamilo_core.settings.', '', $namespace);

        foreach ($parameters as $name => $value) {
            if (isset($persistedParametersMap[$name])) {
                $parameter = $persistedParametersMap[$name];
                $parameter->setSelectedValue($value);
                $parameter->setCategory($simpleCategoryName);
                $this->manager->merge($parameter);
            } else {
                $parameter = new SettingsCurrent();
                $parameter
                    ->setVariable($name)
                    ->setCategory($simpleCategoryName)
                    ->setTitle($name)
                    ->setSelectedValue($value)
                    ->setUrl($url)
                    ->setAccessUrlChangeable(1)
                    ->setAccessUrlLocked(1)
                ;

                /* @var $errors ConstraintViolationListInterface */
                /*$errors = $this->validator->validate($parameter);
                if (0 < $errors->count()) {
                    throw new ValidatorException($errors->get(0)->getMessage());
                }*/
                $this->manager->persist($parameter);
            }
        }

        $this->manager->flush();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ValidatorException
     */
    public function save(SettingsInterface $settings)
    {
        $namespace = $settings->getSchemaAlias();

        /** @var SchemaInterface $schema */
        $schema = $this->schemaRegistry->get($settings->getSchemaAlias());

        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);
        $parameters = $settingsBuilder->resolve($settings->getParameters());
        // Transform value. Example array to string using transformer. Example:
        // 1. Setting "tool_visible_by_default_at_creation" it's a multiple select
        // 2. Is defined as an array in class DocumentSettingsSchema
        // 3. Add transformer for that variable "ArrayToIdentifierTransformer"
        // 4. Here we recover the transformer and convert the array to string
        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $transformer->transform($parameters[$parameter]);
            }
        }
        $settings->setParameters($parameters);
        $persistedParameters = $this->repository->findBy(
            ['category' => $this->convertServiceToNameSpace($settings->getSchemaAlias())]
        );
        $persistedParametersMap = [];

        foreach ($persistedParameters as $parameter) {
            $persistedParametersMap[$parameter->getTitle()] = $parameter;
        }

        /** @var SettingsEvent $event */
        /*$event = $this->eventDispatcher->dispatch(
            SettingsEvent::PRE_SAVE,
            new SettingsEvent($settings, $parameters)
        );*/

        /** @var SettingsCurrent $url */
        $url = $this->getUrl();
        $simpleCategoryName = str_replace('chamilo_core.settings.', '', $namespace);

        foreach ($parameters as $name => $value) {
            if (isset($persistedParametersMap[$name])) {
                $parameter = $persistedParametersMap[$name];
                $parameter->setSelectedValue($value);
            } else {
                $parameter = new SettingsCurrent();
                $parameter
                    ->setVariable($name)
                    ->setCategory($simpleCategoryName)
                    ->setTitle($name)
                    ->setSelectedValue($value)
                    ->setUrl($url)
                    ->setAccessUrlChangeable(1)
                    ->setAccessUrlLocked(1)
                ;

                /* @var $errors ConstraintViolationListInterface */
                /*$errors = $this->validator->validate($parameter);
                if (0 < $errors->count()) {
                    throw new ValidatorException($errors->get(0)->getMessage());
                }*/
                $this->manager->persist($parameter);
            }
            $this->manager->persist($parameter);
        }

        $this->manager->flush();

        return;

        ////
        $schemaAlias = $settings->getSchemaAlias();
        $schemaAliasChamilo = str_replace('chamilo_core.settings.', '', $schemaAlias);

        $schema = $this->schemaRegistry->get($schemaAlias);

        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);

        $parameters = $settingsBuilder->resolve($settings->getParameters());

        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $transformer->transform($parameters[$parameter]);
            }
        }

        /** @var \Sylius\Bundle\SettingsBundle\Event\SettingsEvent $event */
        $event = $this->eventDispatcher->dispatch(
            SettingsEvent::PRE_SAVE,
            new SettingsEvent($settings)
        );

        /** @var SettingsCurrent $url */
        $url = $event->getSettings()->getAccessUrl();

        foreach ($parameters as $name => $value) {
            if (isset($persistedParametersMap[$name])) {
                if ($value instanceof Course) {
                    $value = $value->getId();
                }
                $persistedParametersMap[$name]->setValue($value);
            } else {
                /** @var SettingsCurrent $setting */
                $setting = $this->settingsFactory->createNew();
                $setting->setSchemaAlias($schemaAlias);

                $setting
                    ->setNamespace($schemaAliasChamilo)
                    ->setName($name)
                    ->setValue($value)
                    ->setUrl($url)
                    ->setAccessUrlLocked(0)
                    ->setAccessUrlChangeable(1)
                ;

                /* @var $errors ConstraintViolationListInterface */
                /*$errors = $this->->validate($parameter);
                if (0 < $errors->count()) {
                    throw new ValidatorException($errors->get(0)->getMessage());
                }*/
                $this->manager->persist($setting);
                $this->manager->flush();
            }
        }
        /*$parameters = $settingsBuilder->resolve($settings->getParameters());
        $settings->setParameters($parameters);

        $this->eventDispatcher->dispatch(SettingsEvent::PRE_SAVE, new SettingsEvent($settings));

        $this->manager->persist($settings);
        $this->manager->flush();

        $this->eventDispatcher->dispatch(SettingsEvent::POST_SAVE, new SettingsEvent($settings));*/
    }

    /**
     * @param string $keyword
     *
     * @return array
     */
    public function getParametersFromKeywordOrderedByCategory($keyword)
    {
        $query = $this->repository->createQueryBuilder('s')
            ->where('s.variable LIKE :keyword')
            ->setParameter('keyword', "%$keyword%")
        ;
        $parametersFromDb = $query->getQuery()->getResult();
        $parameters = [];
        /** @var \Chamilo\CoreBundle\Entity\SettingsCurrent $parameter */
        foreach ($parametersFromDb as $parameter) {
            $parameters[$parameter->getCategory()][] = $parameter;
        }

        return $parameters;
    }

    /**
     * @param string $namespace
     * @param string $keyword
     * @param bool   $returnObjects
     *
     * @return array
     */
    public function getParametersFromKeyword($namespace, $keyword = '', $returnObjects = false)
    {
        if (empty($keyword)) {
            $criteria = ['category' => $namespace];
            $parametersFromDb = $this->repository->findBy($criteria);
        } else {
            $query = $this->repository->createQueryBuilder('s')
                ->where('s.variable LIKE :keyword')
                ->setParameter('keyword', "%$keyword%")
            ;
            $parametersFromDb = $query->getQuery()->getResult();
        }

        if ($returnObjects) {
            return $parametersFromDb;
        }
        $parameters = [];
        /** @var \Chamilo\CoreBundle\Entity\SettingsCurrent $parameter */
        foreach ($parametersFromDb as $parameter) {
            $parameters[$parameter->getVariable()] = $parameter->getSelectedValue();
        }

        return $parameters;
    }

    /**
     * Load parameter from database.
     *
     * @param string $namespace
     *
     * @return array
     */
    private function getParameters($namespace)
    {
        $parameters = [];
        /** @var SettingsCurrent $parameter */
        foreach ($this->repository->findBy(['category' => $namespace]) as $parameter) {
            $parameters[$parameter->getVariable()] = $parameter->getSelectedValue();
        }

        return $parameters;
    }

    private function transformParameters(SettingsBuilder $settingsBuilder, array $parameters)
    {
        $transformedParameters = $parameters;

        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (array_key_exists($parameter, $parameters)) {
                $transformedParameters[$parameter] = $transformer->reverseTransform($parameters[$parameter]);
            }
        }

        return $transformedParameters;
    }
}
