<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class SettingsCurrentFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * Settings candidates that must be locked at URL-level (access_url_locked = 1),
     * based on the task list.
     */
    private const ACCESS_URL_LOCKED_YES = [
        'permissions_for_new_directories',
        'permissions_for_new_files',
        'course_creation_form_set_extra_fields_mandatory',
        'access_url_specific_files',
        'cron_remind_course_finished_activate',
        'cron_remind_course_expiration_frequency',
        'cron_remind_course_expiration_activate',
        'donotlistcampus',
        'server_type',
        'chamilo_database_version',
        'unoconv_binaries',
        'session_admin_access_to_all_users_on_all_urls',
        'split_users_upload_directory',
        'multiple_url_hide_disabled_settings',
        'login_is_email',
        'proxy_settings',
        'login_max_attempt_before_blocking_account',
        'permanently_remove_deleted_files',
        'allow_use_sub_language',
    ];

    /**
     * Settings candidates explicitly mentioned as "no" in the task list.
     * We set them to access_url_locked = 0, but only for this candidate list.
     */
    private const ACCESS_URL_LOCKED_NO = [
        'drh_allow_access_to_all_students',
        'ticket_allow_category_edition',
        'max_anonymous_users',
        'enable_x_sendfile_headers',
        'mailer_dsn',
        'allow_send_message_to_all_platform_users',
        'message_max_upload_filesize',
        'use_custom_pages',
        'security_strict_transport',
        'security_content_policy',
        'security_content_policy_report_only',
        'security_public_key_pins',
        'security_public_key_pins_report_only',
        'security_x_frame_options',
        'security_xss_protection',
        'security_x_content_type_options',
        'security_referrer_policy',
        'security_session_cookie_samesite_none',
        'allow_session_admins_to_manage_all_sessions',
        'prevent_session_admins_to_manage_all_users',
        'session_admins_edit_courses_content',
        'assignment_base_course_teacher_access_to_all_session',
    ];

    public static function getGroups(): array
    {
        return ['settings-update'];
    }

    public function load(ObjectManager $manager): void
    {
        $repo = $manager->getRepository(SettingsCurrent::class);

        $existingSettings = $this->flattenConfigurationSettings(self::getExistingSettings());
        $newConfigurationSettings = $this->flattenConfigurationSettings(self::getNewConfigurationSettings());

        $allConfigurations = array_merge($existingSettings, $newConfigurationSettings);

        // Keep current behavior: update title/comment from configuration arrays.
        foreach ($allConfigurations as $settingData) {
            $setting = $repo->findOneBy(['variable' => $settingData['name']]);

            if (!$setting) {
                continue;
            }

            $setting->setTitle($settingData['title']);
            $setting->setComment($settingData['comment']);

            // Only set default value when current value is empty (do not override admins)
            if (\array_key_exists('selected_value', $settingData)) {
                $currentValue = $setting->getSelectedValue();
                if (null === $currentValue || '' === $currentValue) {
                    $setting->setSelectedValue((string) $settingData['selected_value']);
                }
            }

            $manager->persist($setting);
        }

        // Reset all task candidates to access_url_locked = 0 (deterministic baseline).
        $candidates = array_values(array_unique(array_merge(
            self::ACCESS_URL_LOCKED_YES,
            self::ACCESS_URL_LOCKED_NO
        )));

        /** @var SettingsCurrent[] $candidateSettings */
        $candidateSettings = $repo->findBy(['variable' => $candidates]);

        // Index by variable to avoid extra queries.
        $byVariable = [];
        foreach ($candidateSettings as $setting) {
            $byVariable[$setting->getVariable()] = $setting;

            $setting->setAccessUrlLocked(0);
            $manager->persist($setting);
        }

        // Apply access_url_locked = 1 for the explicit YES list.
        foreach (self::ACCESS_URL_LOCKED_YES as $variable) {
            if (!isset($byVariable[$variable])) {
                continue;
            }

            $byVariable[$variable]->setAccessUrlLocked(1);
            $manager->persist($byVariable[$variable]);
        }

        $manager->flush();
    }

    private function flattenConfigurationSettings(array $categorizedSettings): array
    {
        $flattenedSettings = [];
        foreach ($categorizedSettings as $category => $settings) {
            foreach ($settings as $setting) {
                $flattenedSettings[] = $setting;
            }
        }

        return $flattenedSettings;
    }

    public static function getExistingSettings(): array
    {
        // registration.redirect_after_login (default for new installations only)
        $redirectAfterLoginDefault = json_encode([
            'COURSEMANAGER' => 'courses',
            'STUDENT' => 'courses',
            'DRH' => '',
            'SESSIONADMIN' => 'admin-dashboard',
            'STUDENT_BOSS' => 'main/my_space/student.php',
            'INVITEE' => 'courses',
            'ADMIN' => 'admin',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return [
            'profile' => [
                [
                    'name' => 'account_valid_duration',
                    'title' => 'Account validity',
                    'comment' => 'A user account is valid for this number of days after creation',
                ],
                [
                    'name' => 'extended_profile',
                    'title' => 'Portfolio',
                    'comment' => "If this setting is on, a user can fill in the following (optional) fields: 'My personal open area', 'My competences', 'My diplomas', 'What I am able to teach'",
                ],
                [
                    'name' => 'split_users_upload_directory',
                    'title' => "Split users' upload directory",
                    'comment' => "On high-load portals, where a lot of users are registered and send their pictures, the upload directory (main/upload/users/) might contain too many files for the filesystem to handle (it has been reported with more than 36000 files on a Debian server). Changing this option will enable a one-level splitting of the directories in the upload directory. 9 directories will be used in the base directory and all subsequent users' directories will be stored into one of these 9 directories. The change of this option will not affect the directories structure on disk, but will affect the behaviour of the Chamilo code, so if you change this option, you have to create the new directories and move the existing directories by yourself on te server. Be aware that when creating and moving those directories, you will have to move the directories of users 1 to 9 into subdirectories of the same name. If you are not sure about this option, it is best not to activate it.",
                ],
                [
                    'name' => 'user_selected_theme',
                    'title' => 'User theme selection',
                    'comment' => 'Allow users to select their own visual theme in their profile. This will change the look of Chamilo for them, but will leave the default style of the portal intact. If a specific course or session has a specific theme assigned, it will have priority over user-defined themes.',
                ],
                [
                    'name' => 'allow_users_to_change_email_with_no_password',
                    'title' => 'Allow users to change e-mail without password',
                    'comment' => 'When changing the account information',
                ],
                [
                    'name' => 'login_is_email',
                    'title' => 'Use the email as username',
                    'comment' => 'Use the email in order to login to the system',
                ],
                [
                    'name' => 'use_users_timezone',
                    'title' => 'Enable users timezones',
                    'comment' => 'Enable the possibility for users to select their own timezone. Once configured, users will be able to see assignment deadlines and other time references in their own timezone, which will reduce errors at delivery time.',
                ],
                [
                    'name' => 'allow_show_linkedin_url',
                    'title' => 'Allow show the user LinkedIn URL',
                    'comment' => "Add a link on the user social block, allowing visit the user's LinkedIn profile",
                ],
                [
                    'name' => 'allow_show_skype_account',
                    'title' => 'Allow show the user Skype account',
                    'comment' => 'Add a link on the user social block allowing start a chat by Skype',
                ],
                [
                    'name' => 'enable_profile_user_address_geolocalization',
                    'title' => "Enable user's geolocalization",
                    'comment' => "Enable user's address field and show it on a map using geolocalization features",
                ],
                [
                    'name' => 'show_official_code_whoisonline',
                    'title' => "Official code on 'Who is online'",
                    'comment' => "Show official code on the 'Who is online' page, below the username.",
                ],
            ],
            'session' => [
                [
                    'name' => 'career_diagram_disclaimer',
                    'title' => 'Display a disclaimer below the career diagram',
                    'comment' => "Add a disclaimer below the career diagram. A language variable called 'Career diagram disclaimer' must exist in your sub-language.",
                ],
                [
                    'name' => 'career_diagram_legend',
                    'title' => 'Display a legend below the career diagram',
                    'comment' => "Add a career legend below the career diagram. A language variable called 'Career diagram legend' must exist in your sub-language.",
                ],
                [
                    'name' => 'allow_career_users',
                    'title' => 'Enable career diagrams for users',
                    'comment' => 'If career diagrams are enabled, users can only see them (and only the diagrams that correspond to their studies) if you enable this option.',
                ],
                [
                    'name' => 'allow_edit_tool_visibility_in_session',
                    'title' => 'Allow tool visibility edition in sessions',
                    'comment' => 'When using sessions, the default behaviour is to use the tool visibility defined in the base course. This setting changes that to allow coaches in session courses to adapt tool visibilities to their needs.',
                ],
                [
                    'name' => 'courses_list_session_title_link',
                    'title' => 'Type of link for the session title',
                    'comment' => 'On the courses/sessions page, the session title can be either of the following : 0 = no link (hide session title) ; 1 = link title to a special session page ; 2 = link to the course if there is only one course ; 3 = session title makes the courses list foldable ; 4 = no link (show session title).',
                ],
                [
                    'name' => 'user_session_display_mode',
                    'title' => 'My Sessions display mode',
                    'comment' => 'Choose how the "My Sessions" page is displayed: as a modern visual block (card) view or the classic list style.',
                    'selected_value' => 'list',
                ],
                [
                    'name' => 'session_list_view_remaining_days',
                    'title' => 'Show remaining days in My Sessions',
                    'comment' => 'If enabled, the session dates on the "My Sessions" page will be replaced by the number of remaining days.',
                ],
                [
                    'name' => 'add_users_by_coach',
                    'title' => 'Register users by Coach',
                    'comment' => 'Coach users may create users to the platform and subscribe users to a session.',
                ],
                [
                    'name' => 'allow_coach_to_edit_course_session',
                    'title' => 'Allow coaches to edit inside course sessions',
                    'comment' => 'Allow coaches to edit inside course sessions',
                ],
                [
                    'name' => 'extend_rights_for_coach',
                    'title' => 'Extend rights for coach',
                    'comment' => 'Activate this option will give the coach the same permissions as the trainer on authoring tools',
                ],
                [
                    'name' => 'show_session_coach',
                    'title' => 'Show session coach',
                    'comment' => 'Show the global session coach name in session title box in the courses list',
                ],
                [
                    'name' => 'show_session_data',
                    'title' => 'Show session data title',
                    'comment' => 'Show session data comment',
                ],
                [
                    'name' => 'allow_session_admins_to_manage_all_sessions',
                    'title' => 'Allow session administrators to see all sessions',
                    'comment' => 'When this option is not enabled (default), session administrators can only see the sessions they have created. This is confusing in an open environment where session administrators might need to share support time between two sessions.',
                ],
                [
                    'name' => 'hide_courses_in_sessions',
                    'title' => 'Hide courses list in sessions',
                    'comment' => 'When showing the session block in your courses page, hide the list of courses inside that session (only show them inside the specific session screen).',
                ],
                [
                    'name' => 'prevent_session_admins_to_manage_all_users',
                    'title' => 'Prevent session admins to manage all users',
                    'comment' => 'By enabling this option, session admins will only be able to see, in the administration page, the users they created.',
                ],
                [
                    'name' => 'allow_session_course_copy_for_teachers',
                    'title' => 'Allow session-to-session copy for teachers',
                    'comment' => 'Enable this option to let teachers copy their content from one course in a session to a course in another session. By default, this option is only available to platform administrators.',
                ],
                [
                    'name' => 'allow_teachers_to_create_sessions',
                    'title' => 'Allow teachers to create sessions',
                    'comment' => 'Teachers can create, edit and delete their own sessions.',
                ],
                [
                    'name' => 'allow_tutors_to_assign_students_to_session',
                    'title' => 'Tutors can assign students to sessions',
                    'comment' => 'When enabled, course coaches/tutors in sessions can subscribe new users to their session. This option is otherwise only available to administrators and session administrators.',
                ],
                [
                    'name' => 'drh_can_access_all_session_content',
                    'title' => 'HR directors access all session content',
                    'comment' => 'If enabled, human resources directors will get access to all content and users from the sessions (s)he follows.',
                ],
                [
                    'name' => 'limit_session_admin_role',
                    'title' => 'Limit session admins permissions',
                    'comment' => "If enabled, the session administrators will only see the User block with the 'Add user' option and the Sessions block with the 'Sessions list' option.",
                ],
                [
                    'name' => 'my_courses_view_by_session',
                    'title' => 'View my courses by session',
                    'comment' => "Enable an additional 'My courses' page where sessions appear as part of courses, rather than the opposite.",
                ],
                [
                    'name' => 'session_course_ordering',
                    'title' => 'Session courses manual ordering',
                    'comment' => 'Enable this option to allow the session administrators to order the courses inside a session manually. If disabled, courses are ordered alphabetically on course title.',
                ],
                [
                    'name' => 'session_days_after_coach_access',
                    'title' => 'Default coach access days after session',
                    'comment' => 'Default number of days a coach can access his session after the official session end date',
                ],
                [
                    'name' => 'session_days_before_coach_access',
                    'title' => 'Default coach access days before session',
                    'comment' => 'Default number of days a coach can access his session before the official session start date',
                ],
                [
                    'name' => 'show_session_description',
                    'title' => 'Show session description',
                    'comment' => 'Show the session description wherever this option is implemented (sessions tracking pages, etc)',
                ],
            ],
            'admin' => [
                [
                    'name' => 'administrator_email',
                    'title' => 'Portal Administrator: e-mail',
                    'comment' => 'The e-mail address of the Platform Administrator (appears in the footer on the left)',
                ],
                [
                    'name' => 'administrator_name',
                    'title' => 'Portal Administrator: First Name',
                    'comment' => 'The First Name of the Platform Administrator (appears in the footer on the left)',
                ],
                [
                    'name' => 'administrator_phone',
                    'title' => 'Portal Administrator: Phone number',
                    'comment' => 'The phone number of the Platform Administrator (appears in the footer on the left)',
                ],
                [
                    'name' => 'administrator_surname',
                    'title' => 'Portal Administrator: Last Name',
                    'comment' => 'The Family Name of the Platform Administrator (appears in the footer on the left)',
                ],
                [
                    'name' => 'redirect_admin_to_courses_list',
                    'title' => 'Redirect admin to courses list',
                    'comment' => 'The default behaviour is to send administrators directly to the administration panel (while teachers and students are sent to the courses list or the platform homepage). Enable to redirect the administrator also to his/her courses list.',
                ],
            ],
            'course' => [
                [
                    'name' => 'profiling_filter_adding_users',
                    'title' => 'Filter users on profile fields on subscription to course',
                    'comment' => 'Allow teachers to filter the users based on extra fields on the page to subscribe users to their course.',
                ],
                [
                    'name' => 'course_sequence_valid_only_in_same_session',
                    'title' => 'Validate prerequisites only within the same session',
                    'comment' => 'When enabled, a course will be considered validated only if passed within the current session. If disabled, courses passed in other sessions will also unlock dependent courses.',
                ],
                [
                    'name' => 'allow_course_theme',
                    'title' => 'Allow course themes',
                    'comment' => "Allows course graphical themes and makes it possible to change the style sheet used by a course to any of the possible style sheets available to Chamilo. When a user enters the course, the style sheet of the course will have priority over the user's own style sheet and the platform's default style sheet.",
                ],
                [
                    'name' => 'breadcrumbs_course_homepage',
                    'title' => 'Course homepage breadcrumb',
                    'comment' => "The breadcrumb is the horizontal links navigation system usually in the top left of your page. This option selects what you want to appear in the breadcrumb on courses' homepages",
                ],
                [
                    'name' => 'display_coursecode_in_courselist',
                    'title' => 'Display Code in Course name',
                    'comment' => 'Display Course Code in courses list',
                ],
                [
                    'name' => 'display_teacher_in_courselist',
                    'title' => 'Display teacher in course name',
                    'comment' => 'Display teacher in courses list',
                ],
                [
                    'name' => 'enable_tool_introduction',
                    'title' => 'Enable tool introduction',
                    'comment' => "Enable introductions on each tool's homepage",
                ],
                [
                    'name' => 'example_material_course_creation',
                    'title' => 'Example material on course creation',
                    'comment' => 'Create example material automatically when creating a new course',
                ],
                [
                    'name' => 'send_email_to_admin_when_create_course',
                    'title' => 'E-mail alert on course creation',
                    'comment' => 'Send an email to the platform administrator each time a teacher creates a new course',
                ],
                [
                    'name' => 'show_navigation_menu',
                    'title' => 'Display course navigation menu',
                    'comment' => 'Display a navigation menu that quickens access to the tools',
                ],
                [
                    'name' => 'show_toolshortcuts',
                    'title' => 'Tools shortcuts',
                    'comment' => 'Show the tool shortcuts in the banner?',
                ],
                [
                    'name' => 'student_view_enabled',
                    'title' => 'Enable learner view',
                    'comment' => 'Enable the learner view, which allows a teacher or admin to see a course as a learner would see it',
                ],
                [
                    'name' => 'course_hide_tools',
                    'title' => 'Hide tools from teachers',
                    'comment' => 'Check the tools you want to hide from teachers. This will prohibit access to the tool.',
                ],
                [
                    'name' => 'course_validation',
                    'title' => 'Courses validation',
                    'comment' => "When the 'Courses validation' feature is enabled, a teacher is not able to create a course alone. He/she fills a course request. The platform administrator reviews the request and approves it or rejects it.<br />This feature relies on automated e-mail messaging; set Chamilo to access an e-mail server and to use a dedicated an e-mail account.",
                ],
                [
                    'name' => 'course_validation_terms_and_conditions_url',
                    'title' => 'Course validation - a link to the terms and conditions',
                    'comment' => "This is the URL to the 'Terms and Conditions' document that is valid for making a course request. If the address is set here, the user should read and agree with these terms and conditions before sending a course request.<br />If you enable Chamilo's 'Terms and Conditions' module and if you want its URL to be used, then leave this setting empty.",
                ],
                [
                    'name' => 'courses_default_creation_visibility',
                    'title' => 'Default course visibility',
                    'comment' => 'Default course visibility while creating a new course',
                ],
                [
                    'name' => 'scorm_cumulative_session_time',
                    'title' => 'Cumulative session time for SCORM',
                    'comment' => 'When enabled, the session time for SCORM Learning Paths will be cumulative, otherwise, it will only be counted from the last update time. This is a global setting. It is used when creating a new Learning Path but can then be redefined for each one.',
                ],
                [
                    'name' => 'course_creation_use_template',
                    'title' => 'Use template course for new courses',
                    'comment' => 'Set this to use the same template course (identified by its course numeric ID in the database) for all new courses that will be created on the platform. Please note that, if not properly planned, this setting might have a massive impact on space usage. The template course will be used as if the teacher did a copy of the course with the course backup tools, so no user content is copied, only teacher material. All other course-backup rules apply. Leave empty (or set to 0) to disable.',
                ],
                [
                    'name' => 'course_images_in_courses_list',
                    'title' => 'Courses custom icons',
                    'comment' => 'Use course images as the course icon in courses lists (instead of the default green blackboard icon).',
                ],
            ],
            'editor' => [
                [
                    'name' => 'allow_email_editor',
                    'title' => 'Online e-mail editor enabled',
                    'comment' => 'If this option is activated, clicking on an e-mail address will open an online editor.',
                ],
                [
                    'name' => 'enabled_asciisvg',
                    'title' => 'Enable AsciiSVG',
                    'comment' => 'Enable the AsciiSVG plugin in the WYSIWYG editor to draw charts from mathematical functions.',
                ],
                [
                    'name' => 'math_asciimathML',
                    'title' => 'ASCIIMathML mathematical editor',
                    'comment' => 'Enable ASCIIMathML mathematical editor',
                ],
                [
                    'name' => 'allow_spellcheck',
                    'title' => 'Spell check',
                    'comment' => 'Enable spell check',
                ],
                [
                    'name' => 'block_copy_paste_for_students',
                    'title' => 'Block learners copy and paste',
                    'comment' => 'Block learners the ability to copy and paste into the WYSIWYG editor',
                ],
                [
                    'name' => 'enable_iframe_inclusion',
                    'title' => 'Allow iframes in HTML Editor',
                    'comment' => 'Allowing arbitrary iframes in the HTML Editor will enhance the edition capabilities of the users, but it can represent a security risk. Please make sure you can rely on your users (i.e. you know who they are) before enabling this feature.',
                ],
                [
                    'name' => 'enabled_googlemaps',
                    'title' => 'Activate Google maps',
                    'comment' => 'Activate the button to insert Google maps. Activation is not fully realized if not previously edited the file main/inc/lib/fckeditor/myconfig.php and added a Google maps API key.',
                ],
                [
                    'name' => 'enabled_imgmap',
                    'title' => 'Activate Image maps',
                    'comment' => 'Activate the button to insert Image maps. This allows you to associate URLs to areas of an image, creating hotspots.',
                ],
                [
                    'name' => 'enabled_insertHtml',
                    'title' => 'Allow insertion of widgets',
                    'comment' => 'This allows you to embed on your webpages your favorite videos and applications such as vimeo or slideshare and all sorts of widgets and gadgets',
                ],
                [
                    'name' => 'enabled_mathjax',
                    'title' => 'Enable MathJax',
                    'comment' => 'Enable the MathJax library to visualize mathematical formulas. This is only useful if either ASCIIMathML or ASCIISVG settings are enabled.',
                ],
                [
                    'name' => 'enabled_support_svg',
                    'title' => 'Create and edit SVG files',
                    'comment' => 'This option allows you to create and edit SVG (Scalable Vector Graphics) multilayer online, as well as export them to png format images.',
                ],
                [
                    'name' => 'enabled_wiris',
                    'title' => 'WIRIS mathematical editor',
                    'comment' => "Enable WIRIS mathematical editor. Installing this plugin you get WIRIS editor and WIRIS CAS.<br/>This activation is not fully realized unless it has been previously downloaded the <a href='http://www.wiris.com/es/plugins3/ckeditor/download' target='_blank'>PHP plugin for CKeditor WIRIS</a> and unzipped its contents in the Chamilo's directory main/inc/lib/javascript/ckeditor/plugins/.<br/>This is necessary because Wiris is proprietary software and his services are <a href='http://www.wiris.com/store/who-pays' target='_blank'>commercial</a>. To make adjustments to the plugin, edit configuration.ini file or replace his content by the file configuration.ini.default shipped with Chamilo.",
                ],
                [
                    'name' => 'force_wiki_paste_as_plain_text',
                    'title' => 'Forcing pasting as plain text in the wiki',
                    'comment' => 'This will prevent many hidden tags, incorrect or non-standard, copied from other texts to stop corrupting the text of the Wiki after many issues; but will lose some features while editing.',
                ],
                [
                    'name' => 'htmlpurifier_wiki',
                    'title' => 'HTMLPurifier in Wiki',
                    'comment' => 'Enable HTML purifier in the wiki tool (will increase security but reduce style features)',
                ],
                [
                    'name' => 'include_asciimathml_script',
                    'title' => 'Load the Mathjax library in all the system pages',
                    'comment' => "Activate this setting if you want to show MathML-based mathematical formulas and ASCIIsvg-based mathematical graphics not only in the 'Documents' tool, but elsewhere in the system.",
                ],
                [
                    'name' => 'more_buttons_maximized_mode',
                    'title' => 'Buttons bar extended',
                    'comment' => 'Enable button bars extended when the WYSIWYG editor is maximized',
                ],
                [
                    'name' => 'youtube_for_students',
                    'title' => 'Allow learners to insert videos from YouTube',
                    'comment' => 'Enable the possibility that learners can insert Youtube videos',
                ],
            ],
            'group' => [
                [
                    'name' => 'show_groups_to_users',
                    'title' => 'Show classes to users',
                    'comment' => 'Show the classes to users. Classes are a feature that allow you to register/unregister groups of users into a session or a course directly, reducing the administrative hassle. When you pick this option, learners will be able to see in which class they are through their social network interface.',
                ],
                [
                    'name' => 'allow_group_categories',
                    'title' => 'Group categories',
                    'comment' => 'Allow teachers to create categories in the Groups tool?',
                ],
                [
                    'name' => 'hide_course_group_if_no_tools_available',
                    'title' => 'Hide course group if no tool',
                    'comment' => 'If no tool is available in a group and the user is not registered to the group itself, hide the group completely in the groups list.',
                ],
            ],
            'registration' => [
                [
                    'name' => 'user_hide_never_expire_option',
                    'title' => "Hide 'never expires' option for users",
                    'comment' => "Remove the option 'never expires' when creating/editing a user account.",
                ],
                [
                    'name' => 'extldap_config',
                    'title' => 'LDAP connection configuration',
                    'comment' => 'Array defining host and port for the LDAP server.',
                ],
                [
                    'name' => 'redirect_after_login',
                    'title' => 'Redirect after login (per profile)',
                    'comment' => 'Define redirection per profile after login using a JSON object like {"STUDENT":"", "ADMIN":"admin-dashboard"}',
                    'selected_value' => $redirectAfterLoginDefault,
                ],
                [
                    'name' => 'allow_lostpassword',
                    'title' => 'Lost password',
                    'comment' => 'Are users allowed to request their lost password?',
                ],
                [
                    'name' => 'allow_registration',
                    'title' => 'Registration',
                    'comment' => 'Is registration as a new user allowed? Can users create new accounts?',
                ],
                [
                    'name' => 'allow_registration_as_teacher',
                    'title' => 'Registration as teacher',
                    'comment' => 'Can one register as a teacher (with the ability to create courses)?',
                ],
                [
                    'name' => 'allow_terms_conditions',
                    'title' => 'Enable terms and conditions',
                    'comment' => 'This option will display the Terms and Conditions in the register form for new users. Need to be configured first in the portal administration page.',
                ],
                [
                    'name' => 'extendedprofile_registration',
                    'title' => 'Portfolio fields at registration',
                    'comment' => 'Which of the following fields of the portfolio have to be available in the user registration process? This requires that the portfolio option be enabled (see above).',
                ],
                [
                    'name' => 'extendedprofile_registrationrequired',
                    'title' => 'Required portfolio fields in registration',
                    'comment' => 'Which of the following fields of the portfolio are *required* in the user registration process? This requires that the portfolio option be enabled and that the field be also available in the registration form (see above).',
                ],
                [
                    'name' => 'drh_autosubscribe',
                    'title' => 'Human resources director autosubscribe',
                    'comment' => 'Human resources director autosubscribe - not yet available',
                ],
                [
                    'name' => 'platform_unsubscribe_allowed',
                    'title' => 'Allow unsubscription from platform',
                    'comment' => 'By enabling this option, you allow any user to definitively remove his own account and all data related to it from the platform. This is quite a radical action, but it is necessary for portals opened to the public where users can auto-register. An additional entry will appear in the user profile to unsubscribe after confirmation.',
                ],
                [
                    'name' => 'sessionadmin_autosubscribe',
                    'title' => 'Session admin autosubscribe',
                    'comment' => 'Session administrator autosubscribe - not available yet',
                ],
                [
                    'name' => 'student_autosubscribe',
                    'title' => 'Learner autosubscribe',
                    'comment' => 'Learner autosubscribe - not yet available',
                ],
                [
                    'name' => 'teacher_autosubscribe',
                    'title' => 'Teacher autosubscribe',
                    'comment' => 'Teacher autosubscribe - not yet available',
                ],
            ],
            'message' => [
                [
                    'name' => 'allow_message_tool',
                    'title' => 'Internal messaging tool',
                    'comment' => 'Enabling the internal messaging tool allows users to send messages to other users of the platform and to have a messaging inbox.',
                ],
                [
                    'name' => 'allow_send_message_to_all_platform_users',
                    'title' => 'Allow sending messages to any platform user',
                    'comment' => 'Allows you to send messages to any user of the platform, not just your friends or the people currently online.',
                ],
                [
                    'name' => 'message_max_upload_filesize',
                    'title' => 'Max upload file size in messages',
                    'comment' => 'Maximum size for file uploads in the messaging tool (in Bytes)',
                ],
            ],
            'agenda' => [
                [
                    'name' => 'allow_personal_agenda',
                    'title' => 'Personal Agenda',
                    'comment' => 'Can the learner add personal events to the Agenda?',
                ],
            ],
            'social' => [
                [
                    'name' => 'hide_social_groups_block',
                    'title' => 'Hide groups block in social network',
                    'comment' => 'Removes the groups section from the social network view.',
                ],
                [
                    'name' => 'allow_social_tool',
                    'title' => 'Social network tool (Facebook-like)',
                    'comment' => 'The social network tool allows users to define relations with other users and, by doing so, to define groups of friends. Combined with the internal messaging tool, this tool allows tight communication with friends, inside the portal environment.',
                ],
                [
                    'name' => 'allow_students_to_create_groups_in_social',
                    'title' => 'Allow learners to create groups in social network',
                    'comment' => 'Allow learners to create groups in social network',
                ],
            ],
            'display' => [
                [
                    'name' => 'show_tabs',
                    'title' => 'Main menu entries',
                    'comment' => 'Check the entrie you want to see appear in the main menu',
                ],
                [
                    'name' => 'show_tabs_per_role',
                    'title' => 'Main menu entries per role',
                    'comment' => 'Define header tabs visibility per role.',
                ],
                [
                    'name' => 'pdf_logo_header',
                    'title' => 'PDF header logo',
                    'comment' => 'Whether to use the image at var/themes/[your-theme]/images/pdf_logo_header.png as the PDF header logo for all PDF exports (instead of the normal portal logo)',
                ],
                [
                    'name' => 'gravatar_type',
                    'title' => 'Gravatar avatar type',
                    'comment' => "If the Gravatar option is enabled and the user doesn't have a picture configured on Gravatar, this option allows you to choose the type of avatar that Gravatar will generate for each user. Check <a href='http://en.gravatar.com/site/implement/images#default-image'>http://en.gravatar.com/site/implement/images#default-image</a> for avatar types examples.",
                ],
                [
                    'name' => 'display_categories_on_homepage',
                    'title' => 'Display categories on home page',
                    'comment' => 'This option will display or hide courses categories on the portal home page',
                ],
                [
                    'name' => 'show_administrator_data',
                    'title' => 'Platform Administrator Information in footer',
                    'comment' => 'Show the Information of the Platform Administrator in the footer?',
                ],
                [
                    'name' => 'show_back_link_on_top_of_tree',
                    'title' => 'Show back links from categories/courses',
                    'comment' => 'Show a link to go back in the courses hierarchy. A link is available at the bottom of the list anyway.',
                ],
                [
                    'name' => 'show_closed_courses',
                    'title' => 'Display closed courses on login page and portal start page?',
                    'comment' => "Display closed courses on the login page and courses start page? On the portal start page an icon will appear next to the courses to quickly subscribe to each courses. This will only appear on the portal's start page when the user is logged in and when the user is not subscribed to the portal yet.",
                ],
                [
                    'name' => 'show_email_addresses',
                    'title' => 'Show email addresses',
                    'comment' => 'Show email addresses to users',
                ],
                [
                    'name' => 'show_empty_course_categories',
                    'title' => 'Show empty courses categories',
                    'comment' => "Show the categories of courses on the homepage, even if they're empty",
                ],
                [
                    'name' => 'show_number_of_courses',
                    'title' => 'Show courses number',
                    'comment' => 'Show the number of courses in each category in the courses categories on the homepage',
                ],
                [
                    'name' => 'show_teacher_data',
                    'title' => 'Show teacher information in footer',
                    'comment' => 'Show the teacher reference (name and e-mail if available) in the footer?',
                ],
                [
                    'name' => 'show_tutor_data',
                    'title' => "Session's tutor's data is shown in the footer.",
                    'comment' => "Show the session's tutor reference (name and e-mail if available) in the footer?",
                ],
                [
                    'name' => 'showonline',
                    'title' => "Who's Online",
                    'comment' => 'Display the number of persons that are online?',
                ],
                [
                    'name' => 'time_limit_whosonline',
                    'title' => 'Time limit on Who Is Online',
                    'comment' => 'This time limit defines for how many minutes after his last action a user will be considered *online*',
                ],
                [
                    'name' => 'accessibility_font_resize',
                    'title' => 'Font resize accessibility feature',
                    'comment' => 'Enable this option to show a set of font resize options on the top-right side of your campus. This will allow visually impaired to read their course contents more easily.',
                ],
                [
                    'name' => 'enable_help_link',
                    'title' => 'Enable help link',
                    'comment' => 'The Help link is located in the top right part of the screen',
                ],
                [
                    'name' => 'show_admin_toolbar',
                    'title' => 'Show admin toolbar',
                    'comment' => "Shows a global toolbar on top of the page to the designated user roles. This toolbar, very similar to Wordpress and Google's black toolbars, can really speed up complicated actions and improve the space you have available for the learning content, but it might be confusing for some users",
                ],
                [
                    'name' => 'show_hot_courses',
                    'title' => 'Show hot courses',
                    'comment' => 'The hot courses list will be added in the index page',
                ],
                [
                    'name' => 'hide_home_top_when_connected',
                    'title' => 'Hide top content on homepage when logged in',
                    'comment' => 'On the platform homepage, this option allows you to hide the introduction block (to leave only the announcements, for example), for all users that are already logged in. The general introduction block will still appear to users not already logged in.',
                ],
                [
                    'name' => 'hide_logout_button',
                    'title' => 'Hide logout button',
                    'comment' => 'Hide the logout button. This is usually only interesting when using an external login/logout method, for example when using Single Sign On of some sort.',
                ],
            ],
            'language' => [
                [
                    'name' => 'platform_language',
                    'title' => 'Default platform language',
                    'comment' => 'Main language, used by default when no user language is set.',
                ],
                [
                    'name' => 'language_priority_1',
                    'title' => 'Highest priority language',
                    'comment' => 'Primary language selected when multiple language contexts are set.',
                ],
                [
                    'name' => 'language_priority_2',
                    'title' => 'Secondary priority language',
                    'comment' => 'Secondary fallback language if first priority is unavailable or out of context.',
                ],
                [
                    'name' => 'language_priority_3',
                    'title' => 'Third priority language',
                    'comment' => 'Tertiary language fallback if higher priorities fail.',
                ],
                [
                    'name' => 'language_priority_4',
                    'title' => 'Fourth priority language',
                    'comment' => 'Last language fallback option by order of priority.',
                ],
                [
                    'name' => 'allow_use_sub_language',
                    'title' => 'Allow definition and use of sub-languages',
                    'comment' => "By enabling this option, you will be able to define variations for each of the language terms used in the platform's interface, in the form of a new language based on and extending an existing language. You'll find this option in the languages section of the administration panel.",
                ],
                [
                    'name' => 'show_different_course_language',
                    'title' => 'Show course languages',
                    'comment' => 'Show the language each course is in, next to the course title, on the homepage courses list',
                ],
                [
                    'name' => 'auto_detect_language_custom_pages',
                    'title' => 'Enable language auto-detect in custom pages',
                    'comment' => "If you use custom pages, enable this if you want to have a language detector there present the page in the user's browser language, or disable to force the language to be the default platform language.",
                ],
            ],
            'document' => [
                [
                    'name' => 'access_url_specific_files',
                    'title' => 'Enable URL-specific files',
                    'comment' => 'When this feature is enabled on a multi-URL configuration, you can go to the main URL and provide URL-specific versions of any file (in the documents tool). The original file will be replaced by the alternative whenever seeing it from a different URL. This allows you to customize each URL even further, while enjoying the advantage of re-using the same courses many times.',
                ],
                [
                    'name' => 'default_document_quotum',
                    'title' => 'Default hard disk space',
                    'comment' => 'What is the available disk space for a course? You can override the quota for specific course through: platform administration > Courses > modify',
                ],
                [
                    'name' => 'default_group_quotum',
                    'title' => 'Group disk space available',
                    'comment' => 'What is the default hard disk spacde available for a groups documents tool?',
                ],
                [
                    'name' => 'permanently_remove_deleted_files',
                    'title' => 'Deleted files cannot be restored',
                    'comment' => 'Deleting a file in the documents tool permanently deletes it. The file cannot be restored',
                ],
                [
                    'name' => 'permissions_for_new_directories',
                    'title' => 'Permissions for new directories',
                    'comment' => 'The ability to define the permissions settings to assign to every newly created directory lets you improve security against attacks by hackers uploading dangerous content to your portal. The default setting (0770) should be enough to give your server a reasonable protection level. The given format uses the UNIX terminology of Owner-Group-Others with Read-Write-Execute permissions.',
                ],
                [
                    'name' => 'permissions_for_new_files',
                    'title' => 'Permissions for new files',
                    'comment' => 'The ability to define the permissions settings to assign to every newly-created file lets you improve security against attacks by hackers uploading dangerous content to your portal. The default setting (0550) should be enough to give your server a reasonable protection level. The given format uses the UNIX terminology of Owner-Group-Others with Read-Write-Execute permissions. If you use Oogie, take care that the user who launch LibreOffice can write files in the course folder.',
                ],
                [
                    'name' => 'upload_extensions_blacklist',
                    'title' => 'Blacklist - setting',
                    'comment' => "The blacklist is used to filter the files extensions by removing (or renaming) any file which extension figures in the blacklist below. The extensions should figure without the leading dot (.) and separated by semi-column (;) like the following:  exe;com;bat;scr;php. Files without extension are accepted. Letter casing (uppercase/lowercase) doesn't matter.",
                ],
                [
                    'name' => 'upload_extensions_list_type',
                    'title' => 'Type of filtering on document uploads',
                    'comment' => 'Whether you want to use the blacklist or whitelist filtering. See blacklist or whitelist description below for more details.',
                ],
                [
                    'name' => 'upload_extensions_replace_by',
                    'title' => 'Replacement extension',
                    'comment' => 'Enter the extension that you want to use to replace the dangerous extensions detected by the filter. Only needed if you have selected a filter by replacement.',
                ],
                [
                    'name' => 'upload_extensions_skip',
                    'title' => 'Filtering behaviour (skip/rename)',
                    'comment' => "If you choose to skip, the files filtered through the blacklist or whitelist will not be uploaded to the system. If you choose to rename them, their extension will be replaced by the one defined in the extension replacement setting. Beware that renaming doesn't really protect you, and may cause name collision if several files of the same name but different extensions exist.",
                ],
                [
                    'name' => 'upload_extensions_whitelist',
                    'title' => 'Whitelist - setting',
                    'comment' => "The whitelist is used to filter the file extensions by removing (or renaming) any file whose extension does *NOT* figure in the whitelist below. It is generally considered as a safer but more restrictive approach to filtering. The extensions should figure without the leading dot (.) and separated by semi-column (;) such as the following:  htm;html;txt;doc;xls;ppt;jpg;jpeg;gif;sxw. Files without extension are accepted. Letter casing (uppercase/lowercase) doesn't matter.",
                ],
                [
                    'name' => 'documents_default_visibility_defined_in_course',
                    'title' => 'Document visibility defined in course',
                    'comment' => 'The default document visibility for all courses',
                ],
                [
                    'name' => 'pdf_export_watermark_by_course',
                    'title' => 'Enable watermark definition by course',
                    'comment' => 'When this option is enabled, teachers can define their own watermark for the documents in their courses.',
                ],
                [
                    'name' => 'pdf_export_watermark_enable',
                    'title' => 'Enable watermark in PDF export',
                    'comment' => 'By enabling this option, you can upload an image or a text that will be automatically added as watermark to all PDF exports of documents on the system.',
                ],
                [
                    'name' => 'pdf_export_watermark_text',
                    'title' => 'PDF watermark text',
                    'comment' => 'This text will be added as a watermark to the documents exports as PDF.',
                ],
                [
                    'name' => 'show_default_folders',
                    'title' => 'Show in documents tool all folders containing multimedia resources supplied by default',
                    'comment' => 'Multimedia file folders containing files supplied by default organized in categories of video, audio, image and flash animations to use in their courses. Although you make it invisible into the document tool, you can still use these resources in the platform web editor.',
                ],
                [
                    'name' => 'show_documents_preview',
                    'title' => 'Show document preview',
                    'comment' => 'Showing previews of the documents in the documents tool will avoid loading a new page just to show a document, but can result unstable with some older browsers or smaller width screens.',
                ],
                [
                    'name' => 'show_users_folders',
                    'title' => 'Show users folders in the documents tool',
                    'comment' => 'This option allows you to show or hide to teachers the folders that the system generates for each user who visits the tool documents or send a file through the web editor. If you display these folders to the teachers, they may make visible or not the learners and allow each learner to have a specific place on the course where not only store documents, but where they can also create and edit web pages and to export to pdf, make drawings, make personal web templates, send files, as well as create, move and delete directories and files and make security copies from their folders. Each user of course have a complete document manager. Also, remember that any user can copy a file that is visible from any folder in the documents tool (whether or not the owner) to his/her portfolios or personal documents area of social network, which will be available for his/her can use it in other courses.',
                ],
                [
                    'name' => 'students_download_folders',
                    'title' => 'Allow learners to download directories',
                    'comment' => 'Allow learners to pack and download a complete directory from the document tool',
                ],
                [
                    'name' => 'students_export2pdf',
                    'title' => 'Allow learners to export web documents to PDF format in the documents and wiki tools',
                    'comment' => 'This feature is enabled by default, but in case of server overload abuse it, or specific learning environments, might want to disable it for all courses.',
                ],
                [
                    'name' => 'users_copy_files',
                    'title' => 'Allow users to copy files from a course in your personal file area',
                    'comment' => 'Allows users to copy files from a course in your personal file area, visible through the Social Network or through the HTML editor when they are out of a course',
                ],
            ],
            'forum' => [
                [
                    'name' => 'community_managers_user_list',
                    'title' => 'Community managers list',
                    'comment' => 'Provide an array of user IDs that will be considered community managers in the special course designated as global forum. Community managers have additional privileges on the global forum.',
                ],
                [
                    'name' => 'default_forum_view',
                    'title' => 'Default forum view',
                    'comment' => 'What should be the default option when creating a new forum. Any trainer can however choose a different view for every individual forum',
                ],
                [
                    'name' => 'display_groups_forum_in_general_tool',
                    'title' => 'Display group forums in general forum',
                    'comment' => 'Display group forums in the forum tool at the course level. This option is enabled by default (in this case, group forum individual visibilities still act as an additional criteria). If disabled, group forums will only be visible through the group tool, be them public or not.',
                ],
            ],
            'dropbox' => [
                [
                    'name' => 'dropbox_allow_group',
                    'title' => 'Dropbox: allow group',
                    'comment' => 'Users can send files to groups',
                ],
                [
                    'name' => 'dropbox_allow_just_upload',
                    'title' => 'Dropbox: Upload to own dropbox space?',
                    'comment' => 'Allow trainers and users to upload documents to their dropbox without sending  the documents to themselves',
                ],
                [
                    'name' => 'dropbox_allow_mailing',
                    'title' => 'Dropbox: Allow mailing',
                    'comment' => 'With the mailing functionality you can send each learner a personal document',
                ],
                [
                    'name' => 'dropbox_allow_overwrite',
                    'title' => 'Dropbox: Can documents be overwritten',
                    'comment' => 'Can the original document be overwritten when a user or trainer uploads a document with the name of a document that already exist? If you answer yes then you loose the versioning mechanism.',
                ],
                [
                    'name' => 'dropbox_allow_student_to_student',
                    'title' => 'Dropbox: Learner <-> Learner',
                    'comment' => 'Allow users to send documents to other users (peer 2 peer). Users might use this for less relevant documents also (mp3, tests solutions, ...). If you disable this then the users can send documents to the trainer only.',
                ],
                [
                    'name' => 'dropbox_max_filesize',
                    'title' => 'Dropbox: Maximum file size of a document',
                    'comment' => 'How big (in MB) can a dropbox document be?',
                ],
                [
                    'name' => 'dropbox_hide_course_coach',
                    'title' => 'Dropbox: hide course coach',
                    'comment' => 'Hide session course coach in dropbox when a document is sent by the coach to students',
                ],
                [
                    'name' => 'dropbox_hide_general_coach',
                    'title' => 'Hide general coach in dropbox',
                    'comment' => 'Hide general coach name in the dropbox tool when the general coach uploaded the file',
                ],
            ],
            'survey' => [
                [
                    'name' => 'extend_rights_for_coach_on_survey',
                    'title' => 'Extend rights for coachs on surveys',
                    'comment' => 'Activate this option will allow the coachs to create and edit surveys',
                ],
                [
                    'name' => 'survey_email_sender_noreply',
                    'title' => 'Survey e-mail sender (no-reply)',
                    'comment' => 'Should the survey invitations use the coach e-mail address or the no-reply address defined in the main configuration section?',
                ],
            ],
            'gradebook' => [
                [
                    'name' => 'gradebook_enable',
                    'title' => 'Assessments tool activation',
                    'comment' => 'The Assessments tool allows you to assess competences in your organization by merging classroom and online activities evaluations into Performance reports. Do you want to activate it?',
                ],
                [
                    'name' => 'gradebook_number_decimals',
                    'title' => 'Number of decimals',
                    'comment' => 'Allows you to set the number of decimals allowed in a score',
                ],
                [
                    'name' => 'gradebook_score_display_colorsplit',
                    'title' => 'Threshold',
                    'comment' => 'The threshold (in %) under which scores will be colored red',
                ],
                [
                    'name' => 'gradebook_score_display_custom',
                    'title' => 'Competence levels labelling',
                    'comment' => 'Tick the box to enable Competence levels labelling',
                ],
                [
                    'name' => 'gradebook_score_display_upperlimit',
                    'title' => 'Display score upper limit',
                    'comment' => "Tick the box to show the score's upper limit",
                ],
                [
                    'name' => 'gradebook_default_grade_model_id',
                    'title' => 'Default grade model',
                    'comment' => 'This value will be selected by default when creating a course',
                ],
                [
                    'name' => 'gradebook_default_weight',
                    'title' => 'Default weight in Gradebook',
                    'comment' => 'This weight will be use in all courses by default',
                ],
                [
                    'name' => 'gradebook_enable_grade_model',
                    'title' => 'Enable Gradebook model',
                    'comment' => 'Enables the auto creation of gradebook categories inside a course depending of the gradebook models.',
                ],
                [
                    'name' => 'gradebook_locking_enabled',
                    'title' => 'Enable locking of assessments by teachers',
                    'comment' => "Once enabled, this option will enable locking of any assessment by the teachers of the corresponding course. This, in turn, will prevent any modification of results by the teacher inside the resources used in the assessment: exams, learning paths, tasks, etc. The only role authorized to unlock a locked assessment is the administrator. The teacher will be informed of this possibility. The locking and unlocking of gradebooks will be registered in the system's report of important activities",
                ],
                [
                    'name' => 'teachers_can_change_grade_model_settings',
                    'title' => 'Teachers can change the Gradebook model settings',
                    'comment' => 'When editing a Gradebook',
                ],
                [
                    'name' => 'teachers_can_change_score_settings',
                    'title' => 'Teachers can change the Gradebook score settings',
                    'comment' => 'When editing the Gradebook settings',
                ],
                [
                    'name' => 'gradebook_detailed_admin_view',
                    'title' => 'Show additional columns in gradebook',
                    'comment' => 'Show additional columns in the student view of the gradebook with the best score of all students, the relative position of the student looking at the report and the average score of the whole group of students.',
                ],
                [
                    'name' => 'student_publication_to_take_in_gradebook',
                    'title' => 'Assignment considered for gradebook',
                    'comment' => "In the assignments tool, students can upload more than one file. In case there is more than one for a single assignment, which one should be considered when ranking them in the gradebook? This depends on your methodology. Use 'first' to put the accent on attention to detail (like handling in time and handling the right work first). Use 'last' to highlight collaborative and adaptative work.",
                ],
            ],
            'platform' => [
                [
                    'name' => 'disable_copy_paste',
                    'title' => 'Disable copy-pasting',
                    'comment' => 'When enabled, this option disables as well as possible the copy-pasting mechanisms. Useful in restrictive exams setups.',
                ],
                [
                    'name' => 'session_admin_access_to_all_users_on_all_urls',
                    'title' => 'Allow session admins to see all users on all URLs',
                    'comment' => 'If enabled, session admins can search and list users from all access URLs, regardless of their current URL.',
                ],
                [
                    'name' => 'hosting_limit_users_per_course',
                    'title' => 'Global limit of users per course',
                    'comment' => 'Defines a global maximum number of users (teachers included) allowed to be subscribed to any single course in the platform. Set this value to 0 to disable the limit. This helps avoid courses being overloaded in open portals.',
                ],
                [
                    'name' => 'push_notification_settings',
                    'title' => 'Push notification settings (JSON)',
                    'comment' => 'JSON configuration for Push notifications integration.',
                ],
                [
                    'name' => 'donotlistcampus',
                    'title' => 'Do not list this campus on chamilo.org',
                    'comment' => 'By default, Chamilo portals are automatically registered in a public list at chamilo.org, just using the title you gave to this portal (not the URL nor any private data). Check this box to avoid having the title of your portal appear.',
                ],
                [
                    'name' => 'timezone',
                    'title' => 'Default timezone',
                    'comment' => 'Select the default timezone for this portal. This will help set the timezone (if the feature is enabled) for each new user or for any user that has not set a specific timezone yet. Timezones help show all time-related information on screen in the specific timezone of each user.',
                ],
                [
                    'name' => 'chamilo_database_version',
                    'title' => 'Current version of the database schema used by Chamilo',
                    'comment' => 'Displays the current DB version to match the Chamilo core version.',
                ],
                [
                    'name' => 'notification_event',
                    'title' => 'Enable the notification tool for a more impactful communication channel with students',
                    'comment' => 'Activates popup or system notifications for important platform events.',
                ],
                [
                    'name' => 'institution',
                    'title' => 'Organization name',
                    'comment' => 'The name of the organization (appears in the header on the right)',
                ],
                [
                    'name' => 'institution_url',
                    'title' => 'Organization URL (web address)',
                    'comment' => 'The URL of the institutions (the link that appears in the header on the right)',
                ],
                [
                    'name' => 'server_type',
                    'title' => 'Server Type',
                    'comment' => 'Defines the environment type: "prod" (normal production), "validation" (like production but without reporting statistics), or "test" (debug mode with developer tools such as untranslated string indicators).',
                ],
                [
                    'name' => 'site_name',
                    'title' => 'E-learning portal name',
                    'comment' => 'The Name of your Chamilo Portal (appears in the header)',
                ],
                [
                    'name' => 'use_custom_pages',
                    'title' => 'Use custom pages',
                    'comment' => 'Enable this feature to configure specific login pages by role',
                ],
                [
                    'name' => 'allow_my_files',
                    'title' => "Enable 'My Files' section",
                    'comment' => 'Allow users to upload files to a personal space on the platform.',
                ],
                [
                    'name' => 'cookie_warning',
                    'title' => 'Cookie privacy notification',
                    'comment' => 'If enabled, this option shows a banner on top of your platform that asks users to acknowledge that the platform is using cookies necessary to provide the user experience. The banner can easily be acknowledged and hidden by the user. This allows Chamilo to comply with EU web cookies regulations.',
                ],
                [
                    'name' => 'institution_address',
                    'title' => 'Institution address',
                    'comment' => 'Address',
                ],
            ],
            'mail' => [
                [
                    'name' => 'mailer_dsn',
                    'title' => 'Mail DSN',
                    'comment' => \sprintf(
                        'The DSN fully includes all parameters needed to connect to the mail service. You can learn more at %s. Here are a few examples of supported DSN syntaxes: %s',
                        'https://symfony.com/doc/6.4/mailer.html#using-built-in-transports',
                        'https://symfony.com/doc/6.4/mailer.html#using-a-3rd-party-transport'
                    ),
                ],
                [
                    'name' => 'mailer_from_email',
                    'title' => 'Send all e-mails from this e-mail address',
                    'comment' => 'Sets the default email address used in the "from" field of emails.',
                ],
                [
                    'name' => 'mailer_from_name',
                    'title' => 'Send all e-mails as originating from this (organizational) name',
                    'comment' => 'Sets the default display name used for sending platform emails. e.g. "Support team".',
                ],
                [
                    'name' => 'mailer_mails_charset',
                    'title' => 'Mail: character set',
                    'comment' => "In case you need to define the charset to use when sending those e-mails. Leave empty if you're not sure.",
                ],
                [
                    'name' => 'mailer_debug_enable',
                    'title' => 'Mail: Debug',
                    'comment' => 'Select whether you want to enable the e-mail sending debug logs. These will give you more information on what is happening when connecting to the mail service, but are not elegant and might break page design. Only use when there is not user activity.',
                ],
                [
                    'name' => 'mailer_exclude_json',
                    'title' => 'Mail: Avoid using LD+JSON',
                    'comment' => "Some e-mail clients do not understand the descriptive LD+JSON format, showing it as a loose JSON string to the final user. If this is your case, you might want to set the variable below to 'false' to disable this header.",
                ],
                [
                    'name' => 'mailer_dkim',
                    'title' => 'Mail: DKIM headers',
                    'comment' => 'Enter a JSON array of your DKIM configuration settings (see example).',
                ],
                [
                    'name' => 'mailer_xoauth2',
                    'title' => 'Mail: XOAuth2 options',
                    'comment' => 'If you use some XOAuth2-based e-mail service, use this setting in JSON to save your specific configuration (see example) and select XOAuth2 in the mail service setting.',
                ],
            ],
            'search' => [
                [
                    'name' => 'search_enabled',
                    'title' => 'Full-text search feature',
                    'comment' => "Select 'Yes' to enable this feature. It is highly dependent on the Xapian extension for PHP, so this will not work if this extension is not installed on your server, in version 1.x at minimum.",
                ],
                [
                    'name' => 'search_prefilter_prefix',
                    'title' => 'Specific Field for prefilter',
                    'comment' => 'This option let you choose the Specific field to use on prefilter search type.',
                ],
                [
                    'name' => 'search_show_unlinked_results',
                    'title' => 'Full-text search: show unlinked results',
                    'comment' => 'When showing the results of a full-text search, what should be done with the results that are not accessible to the current user?',
                ],
            ],
            'glossary' => [
                [
                    'name' => 'show_glossary_in_extra_tools',
                    'title' => 'Show the glossary terms in extra tools',
                    'comment' => 'From here you can configure how to add the glossary terms in extra tools as learning path and exercice tool',
                ],
            ],
            'chat' => [
                [
                    'name' => 'allow_global_chat',
                    'title' => 'Allow global chat',
                    'comment' => 'Users can chat with each other',
                ],
                [
                    'name' => 'show_chat_folder',
                    'title' => 'Show the history folder of chat conversations',
                    'comment' => 'This will show to theacher the folder that contains all sessions that have been made in the chat, the teacher can make them visible or not learners and use them as a resource',
                ],
                [
                    'name' => 'save_private_conversations_in_documents',
                    'title' => 'Save private conversations in documents',
                    'comment' => 'If enabled, 1:1 private chat messages will be mirrored in the course chat history documents. Recommended to keep disabled for privacy.',
                ],
            ],
            'skill' => [
                [
                    'name' => 'openbadges_backpack',
                    'title' => 'OpenBadges backpack URL',
                    'comment' => 'The URL of the OpenBadges backpack server that will be used by default for all users wanting to export their badges. This defaults to the open and free Mozilla Foundation backpack repository: https://backpack.openbadges.org/',
                ],
                [
                    'name' => 'allow_hr_skills_management',
                    'title' => 'Allow HR skills management',
                    'comment' => 'Allows HR to manage skills',
                ],
                [
                    'name' => 'allow_skills_tool',
                    'title' => 'Allow Skills tool',
                    'comment' => 'Users can see their skills in the social network and in a block in the homepage.',
                ],
                [
                    'name' => 'show_full_skill_name_on_skill_wheel',
                    'title' => 'Show full skill name on skill wheel',
                    'comment' => 'On the wheel of skills, it shows the name of the skill when it has short code.',
                ],
            ],
            'cas' => [
                [
                    'name' => 'cas_activate',
                    'title' => 'Enable CAS authentication',
                    'comment' => "Enabling CAS authentication will allow users to authenticate with their CAS credentials.<br/>Go to <a href='settings.php?category=CAS'>Plugin</a> to add a configurable 'CAS Login' button for your Chamilo campus. Or you can force CAS authentication by setting cas[force_redirect] in app/config/auth.conf.php.",
                ],
                [
                    'name' => 'cas_add_user_activate',
                    'title' => 'Enable CAS user addition',
                    'comment' => 'Enable CAS user addition. To create the user account from the LDAP directory, the extldap_config and extldap_user_correspondance tables must be filled in in app/config/auth.conf.php',
                ],
                [
                    'name' => 'cas_port',
                    'title' => 'Main CAS server port',
                    'comment' => 'The port on which to connect to the main CAS server',
                ],
                [
                    'name' => 'cas_protocol',
                    'title' => 'Main CAS server protocol',
                    'comment' => 'The protocol with which we connect to the CAS server',
                ],
                [
                    'name' => 'cas_server',
                    'title' => 'Main CAS server',
                    'comment' => 'This is the main CAS server which will be used for the authentication (IP address or hostname)',
                ],
                [
                    'name' => 'cas_server_uri',
                    'title' => 'Main CAS server URI',
                    'comment' => 'The path to the CAS service',
                ],
                [
                    'name' => 'update_user_info_cas_with_ldap',
                    'title' => 'Update CAS-authenticated user account information from LDAP',
                    'comment' => 'Makes sure the user firstname, lastname and email address are the same as current values in the LDAP directory',
                ],
            ],
            'exercise' => [
                [
                    'name' => 'enable_quiz_scenario',
                    'title' => 'Enable Quiz scenario',
                    'comment' => "From here you will be able to create exercises that propose different questions depending in the user's answers.",
                ],
                [
                    'name' => 'exercise_max_score',
                    'title' => 'Maximum score of exercises',
                    'comment' => 'Define a maximum score (generally 10,20 or 100) for all the exercises on the platform. This will define how final results are shown to users and teachers.',
                ],
                [
                    'name' => 'exercise_min_score',
                    'title' => 'Minimum score of exercises',
                    'comment' => 'Define a minimum score (generally 0) for all the exercises on the platform. This will define how final results are shown to users and teachers.',
                ],
                [
                    'name' => 'allow_coach_feedback_exercises',
                    'title' => 'Allow coaches to comment in review of exercises',
                    'comment' => 'Allow coaches to edit feedback during review of exercises',
                ],
                [
                    'name' => 'configure_exercise_visibility_in_course',
                    'title' => 'Enable to bypass the configuration of Exercise invisible in session at a base course level',
                    'comment' => 'To enable the configuration of the exercise invisibility in session in the base course to by pass the global configuration. If not set the global parameter is used.',
                ],
                [
                    'name' => 'email_alert_manager_on_new_quiz',
                    'title' => 'Default e-mail alert setting on new quiz',
                    'comment' => 'Whether you want course managers (teachers) to be notified by e-mail when a quiz is answered by a student. This is the default value to be given to all new courses, but each teacher can still change this setting in his/her own course.',
                ],
                [
                    'name' => 'exercise_invisible_in_session',
                    'title' => 'Exercise invisible in Session',
                    'comment' => 'If an exercise is visible in the base course then it appears invisible in the session. If an exercise is invisible in the base course then it does not appear in the session.',
                ],
                [
                    'name' => 'exercise_max_editors_in_page',
                    'title' => 'Max editors in exercise result screen',
                    'comment' => 'Because of the sheer number of questions that might appear in an exercise, the correction screen, allowing the teacher to add comments to each answer, might be very slow to load. Set this number to 5 to ask the platform to only show WYSIWYG editors up to a certain number of answers on the screen. This will speed up the correction page loading time considerably, but will remove WYSIWYG editors and leave only a plain text editor.',
                ],
                [
                    'name' => 'show_official_code_exercise_result_list',
                    'title' => 'Display official code in exercises results',
                    'comment' => "Whether to show the students' official code in the exercises results reports",
                ],
            ],
            'security' => [
                [
                    'name' => 'hide_breadcrumb_if_not_allowed',
                    'title' => "Hide breadcrumb if 'not allowed'",
                    'comment' => 'If the user is not allowed to access a specific page, also hide the breadcrumb. This increases security by avoiding the display of unnecessary information.',
                ],
                [
                    'name' => 'force_renew_password_at_first_login',
                    'title' => 'Force password renewal at first login',
                    'comment' => 'This is one simple measure to increase the security of your portal by asking users to immediately change their password, so the one that was transfered by e-mail is no longer valid and they then will use one that they came up with and that they are the only person to know.',
                ],
                [
                    'name' => 'login_max_attempt_before_blocking_account',
                    'title' => 'Max login attempts before lockdown',
                    'comment' => 'Number of failed login attempts to tolerate before the user account is locked and has to be unlocked by an admin.',
                ],
                [
                    'name' => '2fa_enable',
                    'title' => 'Enable 2FA',
                    'comment' => "Add fields in the password update page to enable 2FA using a TOTP authenticator app. When disabled globally, users won't see 2FA fields and won't be prompted for 2FA at login, even if they had enabled it previously.",
                ],
                [
                    'name' => 'filter_terms',
                    'title' => 'Filter terms',
                    'comment' => 'Give a list of terms, one by line, to be filtered out of web pages and e-mails. These terms will be replaced by ***.',
                ],
                [
                    'name' => 'allow_captcha',
                    'title' => 'CAPTCHA',
                    'comment' => 'Enable a CAPTCHA on the login form, inscription form and lost password form to avoid password hammering',
                ],
                [
                    'name' => 'allow_strength_pass_checker',
                    'title' => 'Password strength checker',
                    'comment' => 'Enable this option to add a visual indicator of password strength, when the user changes his/her password. This will NOT prevent bad passwords to be added, it only acts as a visual helper.',
                ],
                [
                    'name' => 'captcha_number_mistakes_to_block_account',
                    'title' => 'CAPTCHA mistakes allowance',
                    'comment' => 'The number of times a user can make a mistake on the CAPTCHA box before his account is locked out.',
                ],
                [
                    'name' => 'captcha_time_to_block',
                    'title' => 'CAPTCHA account locking time',
                    'comment' => 'If the user reaches the maximum allowance for login mistakes (when using the CAPTCHA), his/her account will be locked for this number of minutes.',
                ],
                [
                    'name' => 'prevent_multiple_simultaneous_login',
                    'title' => 'Prevent simultaneous login',
                    'comment' => 'Prevent users connecting with the same account more than once. This is a good option on pay-per-access portals, but might be restrictive during testing as only one browser can connect with any given account.',
                ],
                [
                    'name' => 'user_reset_password',
                    'title' => 'Enable password reset token',
                    'comment' => 'This option allows to generate a expiring single-use token sent by e-mail to the user to reset his/her password.',
                ],
                [
                    'name' => 'user_reset_password_token_limit',
                    'title' => 'Time limit for password reset token',
                    'comment' => 'The number of seconds before the generated token automatically expires and cannot be used anymore (a new token needs to be generated).',
                ],
                [
                    'name' => 'access_to_personal_file_for_all',
                    'title' => 'Access to personal file for all',
                    'comment' => 'Allows access to all personal files without restriction',
                ],
            ],
            'tracking' => [
                [
                    'name' => 'my_progress_course_tools_order',
                    'title' => "Order of tools on 'My progress' page",
                    'comment' => "Change the order of tools shown on the 'My progress' page for learners. Options include 'quizzes', 'learning_paths' and 'skills'.",
                ],
                [
                    'name' => 'block_my_progress_page',
                    'title' => "Prevent access to 'My progress'",
                    'comment' => "In specific implementations like online exams, you might want to prevent user access to the 'My progress' page.",
                ],
                [
                    'name' => 'tracking_skip_generic_data',
                    'title' => 'Skip generic data in learner self-tracking page',
                    'comment' => "If the 'My progress' page takes too long to load, you might want to remove the processing of generic statistics for the user. In this case enable this setting.",
                ],
                [
                    'name' => 'footer_extra_content',
                    'title' => 'Extra content in footer',
                    'comment' => 'You can add HTML code like meta tags',
                ],
                [
                    'name' => 'header_extra_content',
                    'title' => 'Extra content in header',
                    'comment' => 'You can add HTML code like meta tags',
                ],
                [
                    'name' => 'meta_twitter_creator',
                    'title' => 'Twitter Creator account',
                    'comment' => 'The Twitter Creator is a Twitter account (e.g. @ywarnier) that represents the *person* that created the site. This field is optional.',
                ],
                [
                    'name' => 'meta_twitter_site',
                    'title' => 'Twitter Site account',
                    'comment' => 'The Twitter site is a Twitter account (e.g. @chamilo_news) that is related to your site. It is usually a more temporary account than the Twitter creator account, or represents an entity (instead of a person). This field is required if you want the Twitter card meta fields to show.',
                ],
                [
                    'name' => 'meta_description',
                    'title' => 'Meta description',
                    'comment' => "This will show an OpenGraph Description meta (og:description) in your site's headers",
                ],
                [
                    'name' => 'meta_image_path',
                    'title' => 'Meta image path',
                    'comment' => 'This Meta Image path is the path to a file inside your Chamilo directory (e.g. home/image.png) that should show in a Twitter card or a OpenGraph card when showing a link to your LMS. Twitter recommends an image of 120 x 120 pixels, which might sometimes be cropped to 120x90.',
                ],
                [
                    'name' => 'meta_title',
                    'title' => 'OpenGraph meta title',
                    'comment' => "This will show an OpenGraph Title meta (og:title) in your site's headers",
                ],
            ],
            'attendance' => [
                [
                    'name' => 'allow_delete_attendance',
                    'title' => 'Attendances: enable deletion',
                    'comment' => 'The default behaviour in Chamilo is to hide attendance sheets instead of deleting them, just in case the teacher would do it by mistake. Enable this option to allow teachers to *really* delete attendance sheets.',
                ],
            ],
            'webservice' => [
                [
                    'name' => 'webservice_return_user_field',
                    'title' => 'Webservices return user field',
                    'comment' => "Ask REST webservices (v2.php) to return another identifier for fields related to user ID. This is useful if the external system doesn't really deal with user IDs as they are in Chamilo, as it helps the external system match the user data return with some external data that is know to Chamilo. For example, if you use an external authentication system, you can return the extra field used to match the user with the external authentication system rather than user.id.",
                ],
                [
                    'name' => 'webservice_enable_adminonly_api',
                    'title' => 'Enable admin-only web services',
                    'comment' => 'Some REST web services are marked for admins only and are disabled by default. Enable this feature to give access to these web services (to users with admin credentials, obviously).',
                ],
                [
                    'name' => 'disable_webservices',
                    'title' => 'Disable web services',
                    'comment' => 'If you do not use web services, enable this to avoid any unnecessary security risk.',
                ],
                [
                    'name' => 'allow_download_documents_by_api_key',
                    'title' => 'Allow download course documents by API Key',
                    'comment' => 'Download documents verifying the REST API key for a user',
                ],
                [
                    'name' => 'messaging_allow_send_push_notification',
                    'title' => 'Allow Push Notifications to the Chamilo Messaging mobile app',
                    'comment' => "Send Push Notifications by Google's Firebase Console",
                ],
                [
                    'name' => 'messaging_gdc_api_key',
                    'title' => 'Server key of Firebase Console for Cloud Messaging',
                    'comment' => 'Server key (legacy token) from project credentials',
                ],
                [
                    'name' => 'messaging_gdc_project_number',
                    'title' => 'Sender ID of Firebase Console for Cloud Messaging',
                    'comment' => "You need register a project on <a href='https://console.firebase.google.com/'>Google Firebase Console</a>",
                ],
            ],
            'crons' => [
                [
                    'name' => 'cron_remind_course_expiration_activate',
                    'title' => 'Remind Course Expiration cron',
                    'comment' => 'Enable the Remind Course Expiration cron',
                ],
                [
                    'name' => 'cron_remind_course_expiration_frequency',
                    'title' => 'Frequency for the Remind Course Expiration cron',
                    'comment' => 'Number of days before the expiration of the course to consider to send reminder mail',
                ],
                [
                    'name' => 'cron_remind_course_finished_activate',
                    'title' => 'Send course finished notification',
                    'comment' => 'Whether to send an e-mail to students when their course (session) is finished. This requires cron tasks to be configured (see main/cron/ directory).',
                ],
            ],
            'announcement' => [
                [
                    'name' => 'announcements_hide_send_to_hrm_users',
                    'title' => 'Hide option to send announcements to HR users',
                    'comment' => 'Remove the checkbox to enable sending announcements to users with HR roles (still requires to confirm in the announcements tool).',
                ],
                [
                    'name' => 'hide_global_announcements_when_not_connected',
                    'title' => 'Hide global announcements for anonymous',
                    'comment' => 'Hide platform announcements from anonymous users, and only show them to authenticated users.',
                ],
            ],
            'ticket' => [
                [
                    'name' => 'show_link_bug_notification',
                    'title' => 'Show link to report bug',
                    'comment' => 'Show a link in the header to report a bug inside of our support platform (http://support.chamilo.org). When clicking on the link, the user is sent to the support platform, on a wiki page that describes the bug reporting process.',
                ],
                [
                    'name' => 'show_link_ticket_notification',
                    'title' => 'Show ticket creation link',
                    'comment' => 'Show the ticket creation link to users on the right side of the portal',
                ],
                [
                    'name' => 'ticket_allow_category_edition',
                    'title' => 'Allow tickets categories edition',
                    'comment' => 'Allow category edition by administrators.',
                ],
                [
                    'name' => 'ticket_allow_student_add',
                    'title' => 'Allow users to add tickets',
                    'comment' => 'Allows all users to add tickets not only the administrators.',
                ],
                [
                    'name' => 'ticket_send_warning_to_all_admins',
                    'title' => 'Send ticket warning messages to administrators',
                    'comment' => "Send a message if a ticket was created without a category or if a category doesn't have any administrator assigned.",
                ],
                [
                    'name' => 'ticket_warn_admin_no_user_in_category',
                    'title' => 'Send alert to administrators if tickets category has no one in charge',
                    'comment' => "Send a warning message (e-mail and Chamilo message) to all administrators if there's not a user assigned to a category.",
                ],
            ],
        ];
    }

    public static function getNewConfigurationSettings(): array
    {
        return [
            'catalog' => [
                [
                    'name' => 'course_catalog_settings',
                    'title' => 'Course catalogue settings',
                    'comment' => 'JSON configuration for course catalog: link settings, filters, sort options, and more.',
                ],
                [
                    'name' => 'session_catalog_settings',
                    'title' => 'Session Catalog Settings',
                    'comment' => 'JSON configuration for session catalog: filters and display options.',
                ],
                [
                    'name' => 'show_courses_descriptions_in_catalog',
                    'title' => 'Show Course Descriptions',
                    'comment' => 'Display course descriptions within the catalog listing.',
                ],
                [
                    'name' => 'course_catalog_published',
                    'title' => 'Published Courses Only',
                    'comment' => 'Limit the catalog to only courses marked as published.',
                ],
                [
                    'name' => 'course_catalog_display_in_home',
                    'title' => 'Display Catalog on Homepage',
                    'comment' => 'Show the course catalog block on the platform homepage.',
                ],
                [
                    'name' => 'hide_public_link',
                    'title' => 'Hide Public Link',
                    'comment' => 'Remove the public URL link from course cards.',
                ],
                [
                    'name' => 'only_show_selected_courses',
                    'title' => 'Only Selected Courses',
                    'comment' => 'Show only manually selected courses in the catalog.',
                ],
                [
                    'name' => 'only_show_course_from_selected_category',
                    'title' => 'Only show matching categories in courses catalogue',
                    'comment' => 'When not empty, only the courses from the given categories will appear in the courses catalogue.',
                ],
                [
                    'name' => 'allow_students_to_browse_courses',
                    'title' => 'Allow Student Browsing',
                    'comment' => 'Permit students to browse and filter the course catalog.',
                ],
                [
                    'name' => 'course_catalog_hide_private',
                    'title' => 'Hide Private Courses',
                    'comment' => 'Exclude private courses from the catalog display.',
                ],
                [
                    'name' => 'show_courses_sessions',
                    'title' => 'Show Courses & Sessions',
                    'comment' => 'Include both courses and sessions in catalog results.',
                ],
                [
                    'name' => 'allow_session_auto_subscription',
                    'title' => 'Auto Session Subscription',
                    'comment' => 'Enable automatic subscription to sessions for users.',
                ],
                [
                    'name' => 'course_subscription_in_user_s_session',
                    'title' => 'Subscription in Session View',
                    'comment' => 'Allow users to subscribe to courses directly from their session page.',
                ],
            ],
            'course' => [
                [
                    'name' => 'active_tools_on_create',
                    'title' => 'Active tools on course creation',
                    'comment' => 'Select the tools that will be *active* after the creation of a course.',
                ],
                [
                    'name' => 'allow_base_course_category',
                    'title' => 'Use course categories from top URL',
                    'comment' => 'In multi-URL settings, allow admins and teachers to assign categories from the top URL to courses in the children URLs.',
                ],
                [
                    'name' => 'allow_public_course_with_no_terms_conditions',
                    'title' => 'Access public courses with terms and conditions',
                    'comment' => 'With this option enabled, if a course has public visibility and terms and conditions, those terms are disabled while the course is public.',
                ],
                [
                    'name' => 'block_registered_users_access_to_open_course_contents',
                    'title' => 'Block public courses access to authenticated users',
                    'comment' => "Only show public courses. Do not allow registered users to access courses with 'open' visibility unless they are subscribed to each of these courses.",
                ],
                [
                    'name' => 'course_about_teacher_name_hide',
                    'title' => 'Hide course teacher info on course details page',
                    'comment' => 'On the course details page, hide the teacher information.',
                ],
                [
                    'name' => 'course_category_code_to_use_as_model',
                    'title' => 'Restrict course templates to one course category',
                    'comment' => 'Give a category code to use as course templates. Only those courses will show in the drop-down at course creation time, and users won’t see the courses in this category from the courses catalogue.',
                ],
                [
                    'name' => 'course_configuration_tool_extra_fields_to_show_and_edit',
                    'title' => 'Extra fields to show in course settings',
                    'comment' => 'The fields defined in this array will appear on the course settings page.',
                ],
                [
                    'name' => 'course_creation_by_teacher_extra_fields_to_show',
                    'title' => 'Extra fields to show on course creation form',
                    'comment' => 'The fields defined in this array will appear as additional fields in the course creation form.',
                ],
                [
                    'name' => 'course_creation_donate_link',
                    'title' => 'Donation link on course creation page',
                    'comment' => 'The page the donation message should link to (full URL).',
                ],
                [
                    'name' => 'course_creation_donate_message_show',
                    'title' => 'Show donate message on course creation page',
                    'comment' => 'Add a message box in the course creation page for teachers, asking them to donate to the project.',
                ],
                [
                    'name' => 'course_creation_form_hide_course_code',
                    'title' => 'Remove course code field from course creation form',
                    'comment' => 'If not provided, the course code is generated by default based on the course title, so enable this option to remove the code field from the course creation form altogether.',
                ],
                [
                    'name' => 'course_creation_form_set_course_category_mandatory',
                    'title' => 'Set course category mandatory',
                    'comment' => 'When creating a course, make the course category a required setting.',
                ],
                [
                    'name' => 'course_creation_form_set_extra_fields_mandatory',
                    'title' => 'Extra fields to require on course creation form',
                    'comment' => 'The fields defined in this array will be mandatory in the course creation form.',
                ],
                [
                    'name' => 'course_creation_splash_screen',
                    'title' => 'Splash screen for courses',
                    'comment' => 'Show a splash screen when creating a new course.',
                ],
                [
                    'name' => 'course_creation_user_course_extra_field_relation_to_prefill',
                    'title' => 'Prefill course fields with fields from user',
                    'comment' => 'If not empty, the course creation process will look for some fields in the user profile and auto-fill them for the course. For example, a teacher specialized in digital marketing could automatically set a « digital marketing » flag on each course (s)he creates.',
                ],
                [
                    'name' => 'course_log_default_extra_fields',
                    'title' => 'User extra fields by default in course stats page',
                    'comment' => 'Configure this array with the internal IDs of the extra fields you want to show by default in the main course stats page.',
                ],
                [
                    'name' => 'course_log_hide_columns',
                    'title' => 'Hide columns from course logs',
                    'comment' => 'This array gives you the possibility to configure which columns to hide in the main course stats page and in the total time report.',
                ],
                [
                    'name' => 'course_student_info',
                    'title' => 'Course student info display',
                    'comment' => 'On the ‘My courses’/’My sessions’ pages, show additional information regarding the score, progress and/or certificate acquisition by the student.',
                ],
                [
                    'name' => 'enable_unsubscribe_button_on_my_course_page',
                    'title' => 'Show unsubscribe button in ‘My courses’',
                    'comment' => 'Add a button to unsubscribe from a course on the ‘My courses’ page.',
                ],
                [
                    'name' => 'hide_course_rating',
                    'title' => 'Hide course rating',
                    'comment' => 'The course rating feature comes by default in different places. If you don’t want it, enable this option.',
                ],
                [
                    'name' => 'hide_course_sidebar',
                    'title' => 'Hide courses block in the sidebar',
                    'comment' => 'When on screens where the left menu is visible, do not display the « Courses » section.',
                ],
                [
                    'name' => 'multiple_access_url_show_shared_course_marker',
                    'title' => 'Show multi-URL shared course marker',
                    'comment' => 'Adds a link icon to courses that are shared between URLs, so users (in particular teachers) know they have to take special care when editing the course content.',
                ],
                [
                    'name' => 'my_courses_show_courses_in_user_language_only',
                    'title' => "Only show courses in the user's language",
                    'comment' => "If enabled, this option will hide all courses not set in the user's language.",
                ],
                [
                    'name' => 'resource_sequence_show_dependency_in_course_intro',
                    'title' => 'Show dependencies in course intro',
                    'comment' => 'When using resources sequencing with courses or sessions, show the dependencies of the course on the course’s homepage.',
                ],
                [
                    'name' => 'view_grid_courses',
                    'title' => 'View courses in a grid layout',
                    'comment' => 'View courses in a layout with several courses per line. Otherwise, the layout will show one course per line.',
                ],
                [
                    'name' => 'show_course_duration',
                    'title' => 'Show courses duration',
                    'comment' => 'Display the course duration next to the course title in the course catalogue and the courses list.',
                ],
            ],
            'certificate' => [
                [
                    'name' => 'add_certificate_pdf_footer',
                    'title' => 'Add footer to PDF certificate exports',
                    'comment' => '',
                ],
                [
                    'name' => 'hide_my_certificate_link',
                    'title' => "Hide 'my certificate' link",
                    'comment' => 'Hide the certificates page for non-admin users.',
                ],
                [
                    'name' => 'session_admin_can_download_all_certificates',
                    'title' => 'Allow session admins to download private certificates',
                    'comment' => 'If enabled, session administrators can download certificates even if they are not publicly published.',
                ],
                [
                    'name' => 'allow_public_certificates',
                    'title' => 'Allow public certificates',
                    'comment' => 'User certificates can be view by unregistered users.',
                ],
                [
                    'name' => 'certificate_pdf_orientation',
                    'title' => 'PDF orientation for certificates',
                    'comment' => 'Set ‘portrait’ or ‘landscape’ (technical terms) for PDF certificates.',
                ],
                [
                    'name' => 'allow_general_certificate',
                    'title' => 'Enable general certificate',
                    'comment' => 'A general certificate is a certificate grouping all the accomplishments by the user in the courses (s)he followed.',
                ],
                [
                    'name' => 'hide_certificate_export_link',
                    'title' => 'Certificates: hide PDF export link for all',
                    'comment' => 'Enable to completely remove the possibility to export certificates to PDF (for all users). If enabled, this includes hiding it from students.',
                ],
                [
                    'name' => 'add_gradebook_certificates_cron_task_enabled',
                    'title' => 'Certificates auto-generation on WS call',
                    'comment' => 'When enabled, and when using the WSCertificatesList webservice, this option will make sure that all certificates have been generated by users if they reached the sufficient score in all items defined in gradebooks for all courses and sessions (this might consume considerable processing resources on your server).',
                ],
                [
                    'name' => 'certificate_filter_by_official_code',
                    'title' => 'Certificates filter by official code',
                    'comment' => 'Add a filter on the students official code to the certificates list.',
                ],
                [
                    'name' => 'hide_certificate_export_link_students',
                    'title' => 'Certificates: hide export link from students',
                    'comment' => "If enabled, students won't be able to export their certificates to PDF. This option is available because, depending on the precise HTML structure of the certificate template, the PDF export might be of low quality. In this case, it is best to only show the HTML certificate to students.",
                ],
            ],
            'admin' => [
                [
                    'name' => 'max_anonymous_users',
                    'title' => 'Multiple anonymous users',
                    'comment' => 'Enable this option to allow multiple system users for anonymous users. This is useful when using this platform as a public showroom for some courses. Having multiple anonymous users will let tracking work for the duration of the experience for several users without mixing their data (which could otherwise confuse them).',
                ],
                [
                    'name' => 'chamilo_latest_news',
                    'title' => 'Latest news',
                    'comment' => 'Get the latest news from Chamilo, including security vulnerabilities and events, directly inside your administration panel. These pieces of news will be checked on the Chamilo news server every time you load the administration page and are only visible to administrators.',
                ],
                [
                    'name' => 'chamilo_support',
                    'title' => 'Chamilo support block',
                    'comment' => 'Get pro tips and an easy way to contact official service providers for professional support, directly from the makers of Chamilo. This block appears on your administration page, is only visible by administrators, and refreshes every time you load the administration page.',
                ],
                [
                    'name' => 'send_inscription_notification_to_general_admin_only',
                    'title' => 'Notify global admin only of new users',
                    'comment' => '',
                ],
                [
                    'name' => 'show_link_request_hrm_user',
                    'title' => 'Show link to request bond between user and HRM',
                    'comment' => '',
                ],
                [
                    'name' => 'user_status_option_only_for_admin_enabled',
                    'title' => 'Hide role from normal users',
                    'comment' => "Allows hiding users' role when this option is set to true and the following array sets the corresponding role to 'true'.",
                ],
                [
                    'name' => 'user_status_option_show_only_for_admin',
                    'title' => 'Define which roles are hidden to normal users',
                    'comment' => "The roles set to 'true' will only appear to administrators. Other users will not be able to see them.",
                ],
            ],
            'agenda' => [
                [
                    'name' => 'agenda_reminders_sender_id',
                    'title' => 'ID of the user who officially sends the agenda reminders',
                    'comment' => 'Sets which user appears as the sender of agenda reminder emails.',
                ],
                [
                    'name' => 'agenda_colors',
                    'title' => 'Agenda colours',
                    'comment' => 'Set HTML-code colours for each type of event to change the colour when displaying the event.',
                ],
                [
                    'name' => 'agenda_legend',
                    'title' => 'Agenda colour legends',
                    'comment' => 'Add a small text as legend describing the colours used for the events.',
                ],
                [
                    'name' => 'agenda_on_hover_info',
                    'title' => 'Agenda hover info',
                    'comment' => 'Customize the agenda on cursor hovering. Show agenda comment and/or description.',
                ],
                [
                    'name' => 'allow_agenda_edit_for_hrm',
                    'title' => 'Allow HRM role to edit or delete agenda events',
                    'comment' => 'This gives the HRM a little more power by allowing them to edit/delete agenda events in the course-session.',
                ],
                [
                    'name' => 'allow_careers_in_global_agenda',
                    'title' => 'Link global calendar events with careers and promotions',
                    'comment' => '',
                ],
                [
                    'name' => 'default_calendar_view',
                    'title' => 'Default calendar display mode',
                    'comment' => 'Set this to dayGridMonth, basicWeek, agendaWeek or agendaDay to change the default view of the calendar.',
                ],
                [
                    'name' => 'fullcalendar_settings',
                    'title' => 'Calendar customization',
                    'comment' => 'Extra settings for the agenda, allowing you to configure the specific calendar library we use.',
                ],
                [
                    'name' => 'personal_agenda_show_all_session_events',
                    'title' => 'Display all agenda events in personal agenda',
                    'comment' => 'Do not hide events from expired sessions.',
                ],
                [
                    'name' => 'personal_calendar_show_sessions_occupation',
                    'title' => 'Display sessions occupations in personal agenda',
                    'comment' => '',
                ],
            ],
            'announcement' => [
                [
                    'name' => 'allow_careers_in_global_announcements',
                    'title' => 'Link global announcements with careers and promotions',
                    'comment' => '',
                ],
                [
                    'name' => 'allow_coach_to_edit_announcements',
                    'title' => 'Allow coaches to always edit announcements',
                    'comment' => 'Allow coaches to always edit announcements inside active or past sessions.',
                ],
                [
                    'name' => 'allow_scheduled_announcements',
                    'title' => 'Enable scheduled announcements in sessions',
                    'comment' => 'Allows the sessions managers to set announcements that will be triggered on specific dates or after/before a number of days of start/end of the session. Enabling this feature requires you to setup a cron task.',
                ],
                [
                    'name' => 'course_announcement_scheduled_by_date',
                    'title' => 'Date-based announcements',
                    'comment' => 'Allow teachers to configure announcements that will be sent at specific dates. This requires you to setup a cron task on cron/course_announcement.php running at least once daily.',
                ],
                [
                    'name' => 'disable_announcement_attachment',
                    'title' => 'Disable attachment to announcements',
                    'comment' => 'Even though attachments in this version are dealt in an elegant way and do not multiply on disk, you might want to disable attachments altogether if you want to avoid excesses.',
                ],
                [
                    'name' => 'disable_delete_all_announcements',
                    'title' => 'Disable button to delete all announcements',
                    'comment' => "Select 'Yes' to remove the button to delete all announcements, as this can be used by mistake by teachers.",
                ],
                [
                    'name' => 'hide_announcement_sent_to_users_info',
                    'title' => "Hide 'sent to' in announcements",
                    'comment' => "Select 'Yes' to avoid showing to whom an announcement has been sent.",
                ],
                [
                    'name' => 'hide_send_to_hrm_users',
                    'title' => 'Hide the option to send an announcement copy to HRM',
                    'comment' => "In the announcements form, an option normally appears to allow teachers to send a copy of the announcement to the user's HRM. Set this to 'Yes' to remote the option (and *not* send the copy).",
                ],
            ],
            'document' => [
                [
                    'name' => 'video_features',
                    'title' => 'Video features',
                    'comment' => "Array of extra features you can enable for the video player in Chamilo. Options include 'speed', which allows you to change the playback speed of a video.",
                ],
                [
                    'name' => 'documents_custom_cloud_link_list',
                    'title' => 'Set strict hosts list for cloud links',
                    'comment' => 'The documents tool can integrate links to files in the cloud. The list of cloud services is limited to a hardcoded list, but you can define the ‘links’ array that will contain a list of your own list of services/URLs. The list defined here will replace the default list.',
                ],
                [
                    'name' => 'documents_hide_download_icon',
                    'title' => 'Hide documents download icon',
                    'comment' => 'In the documents tool, hide the download icon from users.',
                ],
                [
                    'name' => 'enable_x_sendfile_headers',
                    'title' => 'Enable X-sendfile headers',
                    'comment' => 'Enable this if you have X-sendfile enabled at the web server level and you want to add the required headers for browsers to pick it up.',
                ],
                [
                    'name' => 'group_category_document_access',
                    'title' => 'Enable sharing options for document inside group category',
                    'comment' => '',
                ],
                [
                    'name' => 'group_document_access',
                    'title' => 'Enable sharing options for group document',
                    'comment' => '',
                ],
                [
                    'name' => 'send_notification_when_document_added',
                    'title' => 'Send notification to students when document added',
                    'comment' => 'Whenever someone creates a new item in the documents tool, send a notification to users.',
                ],
                [
                    'name' => 'thematic_pdf_orientation',
                    'title' => 'PDF orientation for course progress',
                    'comment' => 'In the course progress tool, you can print a PDF of the different elements. Set ‘portrait’ or ‘landscape’ (technical terms) to change it.',
                ],
            ],
            'attendance' => [
                [
                    'name' => 'attendance_allow_comments',
                    'title' => 'Allow comments in attendance sheets',
                    'comment' => 'Teachers and students can comment on each individual attendance (to justify).',
                ],
                [
                    'name' => 'attendance_calendar_set_duration',
                    'title' => 'Duration of attendance events',
                    'comment' => 'Option to define the duration for an event in attendance sheet.',
                ],
                [
                    'name' => 'enable_sign_attendance_sheet',
                    'title' => 'Attendance signing',
                    'comment' => "Enable taking signatures to confirm one's attendance.",
                ],
                [
                    'name' => 'multilevel_grading',
                    'title' => 'Enable Multi-Level Attendance Grading',
                    'comment' => 'Allows grading attendance with multiple levels instead of a simple present/absent system.',
                ],
            ],
            'display' => [
                [
                    'name' => 'table_row_list',
                    'title' => 'Default offered pagination numbers in tables',
                    'comment' => 'Set the options you want to appear in the navigation around a table to show less or more rows on one page. e.g. [50, 100, 200, 500].',
                ],
                [
                    'name' => 'table_default_row',
                    'title' => 'Default number of table rows',
                    'comment' => 'How many rows should be shown in all tables by default.',
                ],
                [
                    'name' => 'hide_complete_name_in_whoisonline',
                    'title' => "Hide the complete username in 'who is online'",
                    'comment' => "The 'who is online' page (if enabled) will show a picture and a name for each user currently online. Enable this option to hide the names.",
                ],
                [
                    'name' => 'hide_main_navigation_menu',
                    'title' => 'Hide main navigation menu',
                    'comment' => 'When using Chamilo for a specific purpose (like one massive online exam), you might want to reduce distraction even more by removing the side menu.',
                ],
                [
                    'name' => 'order_user_list_by_official_code',
                    'title' => 'Order users by official code',
                    'comment' => "Use the 'official code' to sort most students list on the platform, instead of their lastname or firstname.",
                ],
                [
                    'name' => 'gravatar_enabled',
                    'title' => 'Gravatar user pictures',
                    'comment' => "Enable this option to search into the Gravatar repository for pictures of the current user, if the user hasn't defined a picture locally. This is great to auto-fill pictures on your site, in particular if your users are active internet users. Gravatar pictures can be configured easily, based on the e-mail address of a user, at http://en.gravatar.com/",
                ],
                [
                    'name' => 'hide_social_media_links',
                    'title' => 'Hide social media links',
                    'comment' => 'Some pages allow you to promote the portal or a course on social networks. Enable this setting to remove the links.',
                ],
            ],
            'editor' => [
                [
                    'name' => 'editor_block_image_copy_paste',
                    'title' => 'Prevent copy-pasting images in WYSIWYG editor',
                    'comment' => 'Prevent the use of images copy-paste as base64 in the editor to avoid filling the database with images.',
                ],
                [
                    'name' => 'editor_driver_list',
                    'title' => 'List of WYSIWYG files drivers',
                    'comment' => 'Array containing the names of the drivers for files access from the WYSIWYG editor.',
                ],
                [
                    'name' => 'editor_settings',
                    'title' => 'WYSIWYG editor settings',
                    'comment' => 'Generic configuration array to reconfigure the WYSIWYG editor globally.',
                ],
                [
                    'name' => 'enable_uploadimage_editor',
                    'title' => 'Allow images drag&drop in WYSIWYG editor',
                    'comment' => 'Enable image upload as file when doing a copy in the content or a drag and drop.',
                ],
                [
                    'name' => 'full_editor_toolbar_set',
                    'title' => 'Full WYSIWYG editor toolbar',
                    'comment' => 'Show the full toolbar in all WYSIWYG editor boxes around the platform.',
                ],
                [
                    'name' => 'save_titles_as_html',
                    'title' => 'Save titles as HTML',
                    'comment' => 'Allow users to include HTML in title fields in several places. This allows for some styling of titles, notably in test questions.',
                ],
                [
                    'name' => 'translate_html',
                    'title' => 'Support multi-language HTML content',
                    'comment' => 'If enabled, this option allows users to use a ‘lang’ attribute in HTML elements to define the langage the content of that element is written in. Enable multiple elements with different ‘lang’ attributes and Chamilo will display the content in the langage of the user only.',
                ],
                [
                    'name' => 'video_context_menu_hidden',
                    'title' => 'Hide the context menu on video player',
                    'comment' => '',
                ],
                [
                    'name' => 'video_player_renderers',
                    'title' => 'Video player renderers',
                    'comment' => 'Enable player renderers for YouTube, Vimeo, Facebook, DailyMotion, Twitch medias',
                ],
            ],
            'chat' => [
                [
                    'name' => 'course_chat_restrict_to_coach',
                    'title' => 'Restrict course chat to coaches',
                    'comment' => 'Only allow students to talk to the tutors in the course (not other students).',
                ],
                [
                    'name' => 'hide_chat_video',
                    'title' => 'Hide videochat option in global chat',
                    'comment' => '',
                ],
            ],
            'platform' => [
                [
                    'name' => 'use_virtual_keyboard',
                    'title' => 'Use virtual keyboard',
                    'comment' => 'Make a virtual keyboard appear. This is useful when setting up restrictive exams in a physical room where students have no keyboard to limit their ability to cheat.',
                ],
                [
                    'name' => 'hosting_limit_identical_email',
                    'title' => 'Limit identical email usage',
                    'comment' => 'Maximum number of accounts allowed to share the same e-mail address. Set to 0 to disable this limit.',
                ],
                [
                    'name' => 'generate_random_login',
                    'title' => 'Generate random username',
                    'comment' => 'When importing users (batch processes), automatically generate a random string for username. Otherwise, the username will be generated on the basis of the firstname and lastname, or the prefix of the e-mail.',
                ],
                [
                    'name' => 'pdf_img_dpi',
                    'title' => 'PDF export resolution',
                    'comment' => 'This represents the resolution of generated PDF files (in dot per inch, or dpi). The default is 96. Increasing it will give you better resolution PDF files but will also increase the weight and generation time of the files.',
                ],
                [
                    'name' => 'platform_logo_url',
                    'title' => 'URL for alternative platform logo',
                    'comment' => 'Replaces the Chamilo logo by loading a (possibly remote) URL. Make sure this is allowed by your security policies.',
                ],
                [
                    'name' => 'portfolio_advanced_sharing',
                    'title' => 'Enable portfolio advanced sharing',
                    'comment' => 'Decide who can view the posts and comments of the portfolio.',
                ],
                [
                    'name' => 'portfolio_show_base_course_post_in_sessions',
                    'title' => 'Show base course posts in session course',
                    'comment' => 'Decide who can view the posts and comments of the portfolio.',
                ],
                [
                    'name' => 'timepicker_increment',
                    'title' => 'Timepicker increment',
                    'comment' => 'Minimal time increment (in minutes) when selecting a date and time with the timepicker widget. For example, it might not be useful to have less than 5 or 15 minutes increments when talking about assignment submission, availability of a test, start time of a session, etc.',
                ],
                [
                    'name' => 'unoconv_binaries',
                    'title' => 'UNO converter binaries',
                    'comment' => 'Give the system path to the UNO converter library to enable some extra exporting features.',
                ],
                [
                    'name' => 'use_career_external_id_as_identifier_in_diagrams',
                    'title' => 'Use external career ID in diagrams',
                    'comment' => 'If using career diagrams, show an extra field instead of the internal career ID.',
                ],
                [
                    'name' => 'user_status_show_option',
                    'title' => 'Roles display options',
                    'comment' => 'An array of role => true/false that defines whether that role should be shown or hidden.',
                ],
                [
                    'name' => 'user_status_show_options_enabled',
                    'title' => 'Selective display of roles',
                    'comment' => 'Enable to use an array to define which roles should be clearly displayed and which should be hidden.',
                ],
                [
                    'name' => 'access_to_personal_file_for_all',
                    'title' => 'Access to personal file for all',
                    'comment' => 'Allows all users to access, view, and manage their personal files within the system.',
                ],
            ],
            'language' => [
                [
                    'name' => 'allow_course_multiple_languages',
                    'title' => 'Multiple-language courses',
                    'comment' => "Enable courses managed in more than one language. This option adds a language selector within the course page to let users switch easily, and adds a 'multiple_language' extra field to courses which allows for remote management procedures.",
                ],
                [
                    'name' => 'language_flags_by_country',
                    'title' => 'Language flags',
                    'comment' => 'Use country flags for languages. This is not enabled by default because some languages are not strictly attached to a country, which can lead to frustration for some users.',
                ],
                [
                    'name' => 'show_language_selector_in_menu',
                    'title' => 'Language switcher in main menu',
                    'comment' => 'Display a language selector in the main menu that immediately updates the language preference of the user. This can be useful in multilingual portals where learners have to switch from one language to another for their learning.',
                ],
                [
                    'name' => 'template_activate_language_filter',
                    'title' => 'Multiple-language document templates',
                    'comment' => 'Enable document templates (at the platform or course level) to be configured for specific languages.',
                ],
            ],
            'lp' => [
                [
                    'name' => 'lp_show_reduced_report',
                    'title' => 'Learning paths: show reduced report',
                    'comment' => 'Inside the learning paths tool, when a user reviews his own progress (through the stats icon), show a shorten (less detailed) version of the progress report.',
                ],
                [
                    'name' => 'hide_scorm_pdf_link',
                    'title' => 'Hide Learning Path PDF export',
                    'comment' => 'Hide the Learning Path PDF Export icon from the Learning Paths list',
                ],
                [
                    'name' => 'hide_scorm_copy_link',
                    'title' => 'Hide SCORM Copy',
                    'comment' => 'Hide the Learning Path Copy icon from the Learning Paths list',
                ],
                [
                    'name' => 'hide_scorm_export_link',
                    'title' => 'Hide SCORM Export',
                    'comment' => 'Hide the SCORM Export icon from the Learning Paths list',
                ],
                [
                    'name' => 'allow_lp_return_link',
                    'title' => 'Show learning paths return link',
                    'comment' => "Disable this option to hide the 'Return to homepage' button in the learning paths",
                ],
                [
                    'name' => 'lp_prerequisite_on_quiz_unblock_if_max_attempt_reached',
                    'title' => 'Unlock prerequisites after last test attempt',
                    'comment' => 'Allows users to continue in a learning path after using all quiz attempts of a test used as prerequisite for other items.',
                ],
                [
                    'name' => 'add_all_files_in_lp_export',
                    'title' => 'Export all files when exporting a learning path',
                    'comment' => 'When exporting a LP, all files and folders in the same path of an html will be exported too.',
                ],
                [
                    'name' => 'allow_lp_chamilo_export',
                    'title' => 'Export learning paths in the Chamilo backup format',
                    'comment' => 'Enable the possibility to export any of your learning paths in a Chamilo course backup format.',
                ],
                [
                    'name' => 'allow_teachers_to_access_blocked_lp_by_prerequisite',
                    'title' => 'Teachers can access blocked learning paths',
                    'comment' => 'Teachers do not need to pass complete learning paths to have access to a prerequisites-blocked learning path.',
                ],
                [
                    'name' => 'disable_js_in_lp_view',
                    'title' => 'Disable JS in learning paths view',
                    'comment' => 'Disable JS files that Chamilo usually adds to HTML files in the learning path (while displaying them).',
                ],
                [
                    'name' => 'hide_accessibility_label_on_lp_item',
                    'title' => 'Hide requirements label in learning paths',
                    'comment' => 'Hide the pre-requisites tooltip on learning path items. This is mostly an estaethic choice.',
                ],
                [
                    'name' => 'hide_lp_time',
                    'title' => 'Hide time from learning paths records',
                    'comment' => 'Hide learning paths time spent in reports in general.',
                ],
                [
                    'name' => 'lp_minimum_time',
                    'title' => 'Minimum time to complete learning path',
                    'comment' => 'Add a minimum time field to learning paths. If the user has not spent that much time on the learning path, the last item of the learning path cannot be completed.',
                ],
                [
                    'name' => 'lp_view_accordion',
                    'title' => "Foldable learning paths' items",
                    'comment' => '',
                ],
                [
                    'name' => 'show_invisible_exercise_in_lp_toc',
                    'title' => 'Invisible tests visible in learning paths',
                    'comment' => "Make tests marked as 'invisible' in the tests tool appear when they are included in a learning path.",
                ],
                [
                    'name' => 'show_prerequisite_as_blocked',
                    'title' => "Learning path's prerequisites",
                    'comment' => 'On the learning paths lists, display a visual element to show that other learning paths are currently blocked by some prerequisites rule.',
                ],
                [
                    'name' => 'validate_lp_prerequisite_from_other_session',
                    'title' => 'Use learning path item status from other sessions',
                    'comment' => 'Allow users to complete prerequisites in a learning path if the corresponding item was already completed in another session.',
                ],
                [
                    'name' => 'allow_htaccess_import_from_scorm',
                    'title' => 'Allow .htaccess from SCORM packages',
                    'comment' => 'Normally, all .htaccess files are filtered and removed when importing content in Chamilo. This feature allows .htaccess to be imported if it is present in a SCORM package.',
                ],
                [
                    'name' => 'allow_import_scorm_package_in_course_builder',
                    'title' => 'SCORM import within course import',
                    'comment' => 'Enable to copy the directory structure of SCORM packages when restoring a course (from the course maintenance tool).',
                ],
                [
                    'name' => 'allow_lp_subscription_to_usergroups',
                    'title' => 'Learning paths subscription for classes',
                    'comment' => 'Enable subscription to learning paths and learning path categories to groups/classes.',
                ],
                [
                    'name' => 'allow_session_lp_category',
                    'title' => 'Learning paths categories can be managed in sessions',
                    'comment' => '',
                ],
                [
                    'name' => 'disable_my_lps_page',
                    'title' => "Hide 'My learning paths' page",
                    'comment' => "The page 'My learning path' was added in 1.11. Use this option to hide it.",
                ],
                [
                    'name' => 'download_files_after_all_lp_finished',
                    'title' => 'Download button after finishing learning paths',
                    'comment' => "Show download files button after finishing all LP. Example: if ABC is the course code, and 1 and 100 are the doc id, choose: ['courses' => ['ABC' => [1, 100]]].",
                ],
                [
                    'name' => 'force_edit_exercise_in_lp',
                    'title' => 'Edition of tests included in learning paths',
                    'comment' => 'Enable editing tests even if they have been included in a learning path. The default is to prevent edition if the test is in a learning path, because that can affect consistency of tracking among many learners if test modifications are significant.',
                ],
                [
                    'name' => 'lp_allow_export_to_students',
                    'title' => 'Learners can export learning paths',
                    'comment' => 'Enable this to allow learners to download the learning paths as SCORM packages.',
                ],
                [
                    'name' => 'lp_enable_flow',
                    'title' => 'Navigate between learning paths',
                    'comment' => "Add the possibility to select a 'next' learning path and show buttons inside the learning path to move from one to the next.",
                ],
                [
                    'name' => 'lp_fixed_encoding',
                    'title' => 'Fixed encoding in learning path',
                    'comment' => 'Reduce resource usage by ignoring a check on the text encoding in imported learning paths.',
                ],
                [
                    'name' => 'lp_item_prerequisite_dates',
                    'title' => 'Date-based learning path items prerequisites',
                    'comment' => 'Adds the option to define prerequisites with start and end dates for learnpath items.',
                ],
                [
                    'name' => 'lp_menu_location',
                    'title' => 'Learning path menu location',
                    'comment' => "Set this to 'left' or 'right' to change the side of the learning path menu.",
                ],
                [
                    'name' => 'lp_prerequisit_on_quiz_unblock_if_max_attempt_reached',
                    'title' => 'Unlock learning path item if max attempt is reached for test prerequisite',
                    'comment' => '',
                ],
                [
                    'name' => 'lp_prerequisite_use_last_attempt_only',
                    'title' => 'Use last score in learning path test prerequisites',
                    'comment' => 'When a test is used as prerequisite for an item in the learning path, use the last attempt of the test only as validation for the prerequisite (default is to use best attempt).',
                ],
                [
                    'name' => 'lp_prevents_beforeunload',
                    'title' => 'Prevent beforeunload JS event in learning path',
                    'comment' => 'This helps with browser compatibility by preventing tricky JS events to execute.',
                ],
                [
                    'name' => 'lp_score_as_progress_enable',
                    'title' => 'Use learning path score as progress',
                    'comment' => 'This is useful when using SCORM content with only one large SCO. SCORM does not communicate progress, so this is a trick to use the score as progress. Enabling this option will let you configure this on a per-learning path basis.',
                ],
                [
                    'name' => 'lp_show_max_progress_instead_of_average',
                    'title' => 'Show max progress instead of average for learning paths reporting',
                    'comment' => '',
                ],
                [
                    'name' => 'lp_show_max_progress_or_average_enable_course_level_redefinition',
                    'title' => 'Select max progress vs average for learning paths at course level',
                    'comment' => 'Enable redefinition of the setting to show the best progress instead of averages in reporting of learnpaths at a course level.',
                ],
                [
                    'name' => 'lp_start_and_end_date_visible_in_student_view',
                    'title' => 'Display learning path availability to learners',
                    'comment' => 'Show learning paths to learners with their availability dates, rather than hiding them until the date comes.',
                ],
                [
                    'name' => 'lp_subscription_settings',
                    'title' => 'Learning paths subscription settings',
                    'comment' => "Configure additional options for the learning paths subscription feature. Options include 'allow_add_users_to_lp' and 'allow_add_users_to_lp_category'.",
                ],
                [
                    'name' => 'lp_view_settings',
                    'title' => 'Learning path display settings',
                    'comment' => "Configure additional options for the learning paths display. Options include 'show_reporting_icon', 'hide_lp_arrow_navigation', 'show_toolbar_by_default', 'navigation_in_the_middle' and 'add_extra_quit_to_home_icon'.",
                ],
                [
                    'name' => 'scorm_api_extrafield_to_use_as_student_id',
                    'title' => 'Use extra field as student_id in SCORM communication',
                    'comment' => 'Give the name of the extra field to be used as student_id for all SCORM communication.',
                ],
                [
                    'name' => 'scorm_api_username_as_student_id',
                    'title' => 'Use username as student_id in SCORM communication',
                    'comment' => '',
                ],
                [
                    'name' => 'scorm_lms_update_sco_status_all_time',
                    'title' => 'Update SCO status autonomously',
                    'comment' => 'If the SCO is not sending a status, take over and update the status based on what can be observed in Chamilo.',
                ],
                [
                    'name' => 'scorm_upload_from_cache',
                    'title' => 'Upload SCORM from cache dir',
                    'comment' => 'Allow admins to upload a SCORM package (in zip form) into the cache directory and to use it as import source on the SCORM upload page.',
                ],
                [
                    'name' => 'show_hidden_exercise_added_to_lp',
                    'title' => 'Display tests from learning paths even if invisible',
                    'comment' => 'Show hidden exercises that were added to a LP in the exercise list. If we are in a session, the test is invisible in the base course, it is included in a LP and the setting to show it is not specifically set to true, then hide it.',
                ],
                [
                    'name' => 'show_invisible_exercise_in_lp_list',
                    'title' => 'Display tests in list of learning path tests even if invisible',
                    'comment' => '',
                ],
                [
                    'name' => 'show_invisible_lp_in_course_home',
                    'title' => 'Display link to learning path on course home when invisible',
                    'comment' => 'If a learning path is set to invisible but the teacher/coach decided to make it available from the course homepage, this option prevents Chamilo from hiding the link on the course homepage.',
                ],
                [
                    'name' => 'student_follow_page_add_LP_acquisition_info',
                    'title' => 'Add acquisition column in learner follow-up',
                    'comment' => 'Add column to learner follow-up page to show acquisition status by a learner on a learning path.',
                ],
                [
                    'name' => 'student_follow_page_add_LP_invisible_checkbox',
                    'title' => 'Add visibility information for learning paths on learner follow-up page',
                    'comment' => '',
                ],
                [
                    'name' => 'student_follow_page_add_LP_subscription_info',
                    'title' => 'Unlocked information in learning paths list',
                    'comment' => "This adds an 'unlocked' column in the learning paths list if the learner is subscribed to the given learning path and has access to it.",
                ],
                [
                    'name' => 'student_follow_page_hide_lp_tests_average',
                    'title' => 'Hide percentage sign in average of tests in learning paths in learner follow-up',
                    'comment' => "Hides the icon of percentage in 'Average of tests in Learning Paths' indication on a student tracking",
                ],
                [
                    'name' => 'student_follow_page_include_not_subscribed_lp_students',
                    'title' => 'Include learning paths not subscribed to on learner follow-up page',
                    'comment' => '',
                ],
                [
                    'name' => 'ticket_lp_quiz_info_add',
                    'title' => 'Add learning paths and tests info to ticket reporting',
                    'comment' => '',
                ],
            ],
            'exercise' => [
                [
                    'name' => 'add_exercise_best_attempt_in_report',
                    'title' => 'Enable display of best score attempt',
                    'comment' => "Provide a list of courses and tests' IDs that will show the best score attempt for any learner in the reports. ",
                ],
                [
                    'name' => 'allow_edit_exercise_in_lp',
                    'title' => 'Allow teachers to edit tests in learning paths',
                    'comment' => 'By default, Chamilo prevents you from editing tests that are included inside a learning path. This is to avoid changes that would affect learners (past and future) differently regarding the results and/or progress in the learning path. This option allows teachers to bypass this restriction.',
                ],
                [
                    'name' => 'allow_exercise_categories',
                    'title' => 'Enable test categories',
                    'comment' => 'Test categories are not enabled by default because they add a level of complexity. Enable this feature to show all test categories related management icons appear.',
                ],
                [
                    'name' => 'allow_mandatory_question_in_category',
                    'title' => 'Enable selecting mandatory questions',
                    'comment' => 'Enable the selection of mandatory questions in a test when using random categories.',
                ],
                [
                    'name' => 'allow_notification_setting_per_exercise',
                    'title' => 'Test notification settings at test-level',
                    'comment' => 'Enable the configuration of test submission notifications at the test level rather than the course level. Falls back to course-level settings if not defined at test-level.',
                ],
                [
                    'name' => 'allow_quick_question_description_popup',
                    'title' => 'Quick image addition to question',
                    'comment' => 'Enable an additional icon in the test questions list to add an image as question description. This vastly accelerates question edition when the questions are in the title and the description only includes an image.',
                ],
                [
                    'name' => 'allow_quiz_question_feedback',
                    'title' => 'Add question feedback if incorrect answer',
                    'comment' => 'By default, Chamilo allows you to show feedback on each answer in a question. With this option, an additional field is created to provide pre-defined feedback to the whole question. This feedback will only appear if the user answered incorrectly.',
                ],
                [
                    'name' => 'allow_quiz_results_page_config',
                    'title' => 'Enable test results page configuration',
                    'comment' => "Define an array of settings you want to apply to all tests results pages. Settings can be 'hide_question_score', 'hide_expected_answer', 'hide_category_table', 'hide_correct_answered_questions', 'hide_total_score' and possibly more in the future. Look for ‘getPageConfigurationAttribute’ in the code to see what’s in use.",
                ],
                [
                    'name' => 'allow_quiz_show_previous_button_setting',
                    'title' => "Show 'previous' button in test to navigate questions",
                    'comment' => "Set this to false to disable the 'previous' button when answering questions in a test, thus forcing users to always move ahead.",
                ],
                [
                    'name' => 'allow_teacher_comment_audio',
                    'title' => 'Audio feedback to submitted answers',
                    'comment' => 'Allow teachers to provide feedback to users through audio (alternatively to text) on each question in a test.',
                ],
                [
                    'name' => 'allow_time_per_question',
                    'title' => 'Enable time per question in tests',
                    'comment' => 'By default, it is only possible to limit the time per test. Limiting it per question adds an extra layer of possibilities, and you can (carefully) combine both.',
                ],
                [
                    'name' => 'block_category_questions',
                    'title' => 'Lock questions of previous categories in a test',
                    'comment' => "When using this option, an additional option will appear in the test's configuration. When using a test with multiple question categories and asking for a distribution by category, this will allow the user to navigate questions per category. Once a category is finished, (s)he moves to the next category and cannot return to the previous category.",
                ],
                [
                    'name' => 'block_quiz_mail_notification_general_coach',
                    'title' => 'Block sending test notifications to general coach',
                    'comment' => 'Learners completing a test usually sends notifications to coaches, including the general session coach. Enable this option to omit the general coach from these notifications.',
                ],
                [
                    'name' => 'disable_clean_exercise_results_for_teachers',
                    'title' => "Disable 'clean results' for teachers",
                    'comment' => 'Disable the option to delete test results from the tests list. This is often used when less-careful teachers manage courses, to avoid critical mistakes.',
                ],
                [
                    'name' => 'exercise_additional_teacher_modify_actions',
                    'title' => 'Additional links for teachers in tests list',
                    'comment' => "Configure callback elements to generate new action icons for teachers to the right side of the tests list, in the form of an array, e.g. ['myplugin' => ['MyPlugin', 'urlGeneratorCallback']]",
                ],
                [
                    'name' => 'exercise_attempts_report_show_username',
                    'title' => 'Show username in test results page',
                    'comment' => 'Show the username (instead or, or as well as, the user info) on the test results page.',
                ],
                [
                    'name' => 'exercise_category_report_user_extra_fields',
                    'title' => 'Add user extra fields in exercise category report',
                    'comment' => 'Define an array with the list of user extra fields to add to the report.',
                ],
                [
                    'name' => 'exercise_category_round_score_in_export',
                    'title' => 'Round score in test exports',
                    'comment' => '',
                ],
                [
                    'name' => 'exercise_embeddable_extra_types',
                    'title' => 'Embeddable question types',
                    'comment' => 'By default, only single answer and multiple answer questions are considered when deciding whether a test can be embedded in a video or not. With this option, you can decide that more question types are available. Be aware that not all question types fit nicely in the space assigned to videos. Questions types are availalble in the code in question.class.php.',
                ],
                [
                    'name' => 'exercise_hide_ip',
                    'title' => 'Hide user IP from test reports',
                    'comment' => 'By default, we show user information and its IP address, but this might be considered personal data, so this option allows you to remove this info from all test reports.',
                ],
                [
                    'name' => 'exercise_hide_label',
                    'title' => 'Hide question ribbon (right/wrong) in test results',
                    'comment' => 'In test results, a ribbon appears by default to indicate if the answer was right or wrong. Enable this option to remove the ribbon globally.',
                ],
                [
                    'name' => 'exercise_result_end_text_html_strict_filtering',
                    'title' => 'Bypass HTML filtering in test end messages',
                    'comment' => 'Consider messages at the end of tests are always safe. Removing the filter makes it possible to use JavaScript there.',
                ],
                [
                    'name' => 'exercise_score_format',
                    'title' => 'Tests score format',
                    'comment' => "Select between the following forms for the display of users' score in various reports: 1 = SCORE_AVERAGE (5 / 10); 2 = SCORE_PERCENT (50%); 3 = SCORE_DIV_PERCENT (5 / 10 (50%)). Use the numerical ID of the form you want to use.",
                ],
                [
                    'name' => 'exercises_disable_new_attempts',
                    'title' => 'Disable new test attempts',
                    'comment' => 'Disable new test attempts globally. Usually used when there is a problem with tests in general and you want some time to analyse without blocking the whole platform.',
                ],
                [
                    'name' => 'hide_free_question_score',
                    'title' => "Hide open questions' score",
                    'comment' => 'Hide the fact that open questions (including audio and annotations) have a score by hiding the score display in all learner-facing reports.',
                ],
                [
                    'name' => 'hide_user_info_in_quiz_result',
                    'title' => 'Hide user info on test results page',
                    'comment' => 'The default test results page shows a user datasheet (photo, name, etc) which might, in some contexts, be considered as pushing the limits of personal data treatment. Enable this option to remove user details from the test results.',
                ],
                [
                    'name' => 'limit_exercise_teacher_access',
                    'title' => "Limit teachers' permissions over tests",
                    'comment' => 'When enabled, teachers cannot delete tests nor questions, change tests visibility, download to QTI, clean results, etc.',
                ],
                [
                    'name' => 'my_courses_show_pending_exercise_attempts',
                    'title' => 'Global pending tests list',
                    'comment' => 'Enable to display to the final user a page with the list of pending tests across all courses.',
                ],
                [
                    'name' => 'question_exercise_html_strict_filtering',
                    'title' => 'Bypass HTML filtering in test questions',
                    'comment' => 'Consider questions text in tests are always safe. Removing the filter makes it possible to use JavaScript there.',
                ],
                [
                    'name' => 'question_pagination_length',
                    'title' => 'Question pagination length for teachers',
                    'comment' => 'Number of questions to show on every page when using the question pagination for teachers option.',
                ],
                [
                    'name' => 'quiz_answer_extra_recording',
                    'title' => 'Enable extra test answers recording',
                    'comment' => 'Enable recording of all answers (even temporary) in the track_e_attempt_recording table. This feautre is experimentaland can create issues in the reporting pages when attempting to grade a test.',
                ],
                [
                    'name' => 'quiz_check_all_answers_before_end_test',
                    'title' => 'Check all answers before submitting test',
                    'comment' => 'Display a popup with the list of answered/unanswered questions before submitting the test.',
                ],
                [
                    'name' => 'quiz_check_button_enable',
                    'title' => 'Add answer-saving process check before test',
                    'comment' => 'Make sure users are all set to start the test by providing a simulation of the question-saving process before entering the test. This allows for early detection of some connection issues and reduces user experience frictions.',
                ],
                [
                    'name' => 'quiz_confirm_saved_answers',
                    'title' => 'Add checkbox for answers count confirmation',
                    'comment' => 'This option adds a checkbox at the end of each test asking the user to confirm the number of answers saved. This provides better auditing data for critical tests.',
                ],
                [
                    'name' => 'quiz_discard_orphan_in_course_export',
                    'title' => 'Discard orphan questions in course export',
                    'comment' => 'When exporting a course, do not export the questions that are not part of any test.',
                ],
                [
                    'name' => 'quiz_generate_certificate_ending',
                    'title' => 'Generate certificate on test end',
                    'comment' => 'Generate certificate when ending a quiz. The quiz needs to be linked in the gradebook tool and have a pass percentage configured.',
                ],
                [
                    'name' => 'quiz_hide_attempts_table_on_start_page',
                    'title' => 'Hide test attempts table on test start page',
                    'comment' => 'Hide the table showing all previous attempts on the test start page.',
                ],
                [
                    'name' => 'quiz_hide_question_number',
                    'title' => 'Hide question number',
                    'comment' => 'Hide the question incremental numbering when taking a test.',
                ],
                [
                    'name' => 'quiz_image_zoom',
                    'title' => 'Enable test images zooming',
                    'comment' => 'Enable this feature to allow users to zoom on images used in the tests.',
                ],
                [
                    'name' => 'quiz_keep_alive_ping_interval',
                    'title' => 'Keep session active in tests',
                    'comment' => 'Keep session active by maintaining a regular ping signal to the server every x seconds, define here. We recommend once every 300 seconds.',
                ],
                [
                    'name' => 'quiz_open_question_decimal_score',
                    'title' => 'Decimal score in open question types',
                    'comment' => 'Allow the teacher to rate the open, oral expression and annotation question types with a decimal score.',
                ],
                [
                    'name' => 'quiz_prevent_copy_paste',
                    'title' => 'Block copy-pasting in tests',
                    'comment' => 'Block copy/paste/save/print keys and right-clicks in exercises.',
                ],
                [
                    'name' => 'quiz_question_delete_automatically_when_deleting_exercise',
                    'title' => 'Automatically delete questions when deleting test',
                    'comment' => 'The default behaviour is to make questions orphan when the only test using them is deleted. When enabled, this option ensure that all questions that would otherwise end up orphan are deleted as well.',
                ],
                [
                    'name' => 'quiz_results_answers_report',
                    'title' => 'Show link to download test results',
                    'comment' => 'On the test results page, display a link to download the results as a file.',
                ],
                [
                    'name' => 'quiz_show_description_on_results_page',
                    'title' => 'Always show test description on results page',
                    'comment' => '',
                ],
                [
                    'name' => 'score_grade_model',
                    'title' => 'Score grades model',
                    'comment' => 'Define an array of score ranges and colors to display reports using this model. This allows you to show colors rather than numerical grades.',
                ],
                [
                    'name' => 'send_score_in_exam_notification_mail_to_manager',
                    'title' => 'Add score in mail notification of test submission',
                    'comment' => "Add the learner's score to the e-mail notification sent to the teacher after a test was submitted.",
                ],
                [
                    'name' => 'show_exercise_attempts_in_all_user_sessions',
                    'title' => 'Show test attempts from all sessions in pending tests report',
                    'comment' => 'Show test attempts from users in all sessions where the general coach has access in pending tests report.',
                ],
                [
                    'name' => 'show_exercise_expected_choice',
                    'title' => 'Show expected choice in test results',
                    'comment' => 'Show the expected choice and a status (right/wrong) for each answer on the test results page (if the test has been configured to show results).',
                ],
                [
                    'name' => 'show_exercise_question_certainty_ribbon_result',
                    'title' => 'Show score for certainty degree questions',
                    'comment' => 'By default, Chamilo does not show a score for the certainty degree question types.',
                ],
                [
                    'name' => 'show_exercise_session_attempts_in_base_course',
                    'title' => 'Show test attempts from all sessions in base course',
                    'comment' => 'Show test attempts from users in all sessions to the teacher in the base course.',
                ],
                [
                    'name' => 'show_question_id',
                    'title' => 'Show question IDs in tests',
                    'comment' => "Show questions' internal IDs to let users take note of issues on specific questions and report them more efficiently.",
                ],
                [
                    'name' => 'show_question_pagination',
                    'title' => 'Show question pagination for teachers',
                    'comment' => 'For tests with many questions, use pagination if the number of questions is higher than this setting. Set to 0 to prevent using pagination.',
                ],
                [
                    'name' => 'tracking_my_progress_show_deleted_exercises',
                    'title' => "Show deleted tests in 'My progress'",
                    'comment' => "Enable this option to display, on the 'My progress' page, the results of all tests you have taken, even the ones that have been deleted.",
                ],
            ],
            'forum' => [
                [
                    'name' => 'allow_forum_category_language_filter',
                    'title' => 'Forum categories language filter',
                    'comment' => "Add a language filter to the forum view to only see categries configured in a specific language. Requires using the 'language' extra field on the 'forum_category' entity.",
                ],
                [
                    'name' => 'allow_forum_post_revisions',
                    'title' => 'Forum post review',
                    'comment' => "Enable this option to allow asking for a review or a translation to one's post in a forum. When extensively configured, can be used to collaborate with other users in a language-learning forum.",
                ],
                [
                    'name' => 'forum_fold_categories',
                    'title' => 'Fold forum categories',
                    'comment' => 'Visual effect to enable forum categories folding/unfolding.',
                ],
                [
                    'name' => 'global_forums_course_id',
                    'title' => 'Use course as global forum',
                    'comment' => "Set the course ID (numerical) of a course reserverd to use as a global forum. This replaces the 'Social groups' link in the social network by a link to the forum of that course.",
                ],
                [
                    'name' => 'hide_forum_post_revision_language',
                    'title' => 'Hide forum post review language',
                    'comment' => 'Hide the possibility to assign a language to a forum post review.',
                ],
                [
                    'name' => 'subscribe_users_to_forum_notifications_also_in_base_course',
                    'title' => 'Forum notifications from base course as well',
                    'comment' => 'Enable this option to enable notifications coming from the base course forum, even if following the course through a session.',
                ],
            ],
            'gradebook' => [
                [
                    'name' => 'my_display_coloring',
                    'title' => 'Display colors for scores in the gradebook',
                    'comment' => 'Enables color coding for better score visibility in the gradebook.',
                ],
                [
                    'name' => 'allow_gradebook_comments',
                    'title' => 'Gradebook comments',
                    'comment' => 'Enable gradebook comments so teachers can add a comment to the overall performance of the learner in this course. The comment will appear in the PDF export for the learner.',
                ],
                [
                    'name' => 'allow_gradebook_stats',
                    'title' => 'Cache results in the gradebook',
                    'comment' => 'Put some of the large calculations of averages in cached fields for the links and evaluations to increase speed (considerably). The potential negative impact is that it can take some time to refresh the gradebook results tables.',
                ],
                [
                    'name' => 'gradebook_badge_sidebar',
                    'title' => 'Gradebook badges sidebar',
                    'comment' => 'Generate a block inside the side menu where a few badges can be shown as pending approval. Requires gradebooks to be listed here, by (numerical) ID.',
                ],
                [
                    'name' => 'gradebook_dependency',
                    'title' => 'Inter-gradebook dependencies',
                    'comment' => 'Enables a mechanism of gradebook dependencies that lets people know which other items they need to go through first in order to complete the gradebook.',
                ],
                [
                    'name' => 'gradebook_dependency_mandatory_courses',
                    'title' => 'Mandatory courses for gradebook dependencies',
                    'comment' => 'When using inter-gradebook dependencies, you can choose a list of mandatory courses that will be required before approving any gradebook that has dependencies.',
                ],
                [
                    'name' => 'gradebook_display_extra_stats',
                    'title' => 'Gradebook extra statistics',
                    'comment' => "Add additional columns to the gradebook's main report (1 = ranking, 2 = best score, 3 = average).",
                ],
                [
                    'name' => 'gradebook_enable_subcategory_skills_independant_assignement',
                    'title' => "Enable skills by gradebook's subcategory",
                    'comment' => 'Skills are normally attributed for completing a whole gradebook. By enabling this option, you allow skills to be attached to sub-sections of gradebooks.',
                ],
                [
                    'name' => 'gradebook_flatview_extrafields_columns',
                    'title' => 'User extra fields in gradebook flat view',
                    'comment' => "Add the given columns ('variables' array) to the main results table in the gradebook.",
                ],
                [
                    'name' => 'gradebook_hide_graph',
                    'title' => 'Hide gradebook charts',
                    'comment' => 'If your portal is resources-limited, reducing the generation of the dynamic gradebok charts with potentially thousands of results is a good option.',
                ],
                [
                    'name' => 'gradebook_hide_link_to_item_for_student',
                    'title' => 'Hide item links for learners in gradebook',
                    'comment' => 'Avoid learners clicking on items from the gradebook by removing the links on the items.',
                ],
                [
                    'name' => 'gradebook_hide_pdf_report_button',
                    'title' => "Hide gradebook button 'download PDF report'",
                    'comment' => '',
                ],
                [
                    'name' => 'gradebook_hide_table',
                    'title' => 'Hide gradebook table for learners',
                    'comment' => 'Reduce gradebook load time by hiding the results table (but still giving access to certificates, skills, etc).',
                ],
                [
                    'name' => 'gradebook_multiple_evaluation_attempts',
                    'title' => 'Allow multiple evaluation attempts in gradebook',
                    'comment' => '',
                ],
                [
                    'name' => 'gradebook_pdf_export_settings',
                    'title' => 'Gradebook PDF export options',
                    'comment' => "Change the PDF export for learners based on the provided settings ('hide_score_weight', 'hide_feedback_textarea', ...)",
                ],
                [
                    'name' => 'gradebook_report_score_style',
                    'title' => 'Gradebook reports score style',
                    'comment' => 'Add gradebook score style configuration in the flat view. See api.lib.php in order to find the options: examples SCORE_DIV = 1, SCORE_PERCENT = 2, etc',
                ],
                [
                    'name' => 'gradebook_score_display_custom_standalone',
                    'title' => "Custom score display in gradebook's standalone column",
                    'comment' => '',
                ],
                [
                    'name' => 'gradebook_use_apcu_cache',
                    'title' => 'Use APCu caching to speed up gradebok',
                    'comment' => 'Improve speed when rendering gradebook student reports using Doctrine APCU cache. APCu is an optional but recommended PHP extension.',
                ],
                [
                    'name' => 'gradebook_use_exercise_score_settings_in_categories',
                    'title' => 'Use test settings for grades display',
                    'comment' => '',
                ],
                [
                    'name' => 'gradebook_use_exercise_score_settings_in_total',
                    'title' => 'Use global score display setting in gradebook',
                    'comment' => '',
                ],
                [
                    'name' => 'hide_gradebook_percentage_user_result',
                    'title' => 'Hide percentage in best/average gradebook results',
                    'comment' => '',
                ],
            ],
            'glossary' => [
                [
                    'name' => 'allow_remove_tags_in_glossary_export',
                    'title' => 'Remove HTML tags in glossary export',
                    'comment' => '',
                ],
                [
                    'name' => 'default_glossary_view',
                    'title' => 'Default glossary view',
                    'comment' => "Choose which view ('table' or 'list') will be used by default in the glossary tool.",
                ],
            ],
            'profile' => [
                [
                    'name' => 'show_terms_if_profile_completed',
                    'title' => 'Terms and conditions only if profile complete',
                    'comment' => "By enabling this option, terms and conditions will be available to the user only when the extra profile fields that start with 'terms_' and set to visible are completed.",
                ],
                [
                    'name' => 'allow_user_headings',
                    'title' => 'Allow users profiling inside courses',
                    'comment' => 'Can a teacher define learner profile fields to retrieve additional information?',
                ],
                [
                    'name' => 'visible_options',
                    'title' => 'List of visible fields in profile',
                    'comment' => 'Controls which profile fields are visible to users and others.',
                ],
                [
                    'name' => 'add_user_course_information_in_mailto',
                    'title' => 'Pre-fill the mail with user and course info in footer contact',
                    'comment' => 'Add subject and body in the mailto: footer.',
                ],
                [
                    'name' => 'changeable_options',
                    'title' => 'Fields users are allowed to change in their profile',
                    'comment' => 'Select the fields users will be able to change on their profile page.',
                ],
                [
                    'name' => 'hide_username_in_course_chat',
                    'title' => 'Hide username in course chat',
                    'comment' => "In the course chat, hide the username. Only display people's names.",
                ],
                [
                    'name' => 'hide_username_with_complete_name',
                    'title' => 'Hide username when already showing complete name',
                    'comment' => "Some internal functions will return the username when returning the user's complete name. With this option enabled, you ensure the username will not appear.",
                ],
                [
                    'name' => 'my_space_users_items_per_page',
                    'title' => 'Default number of items per page in mySpace',
                    'comment' => '',
                ],
                [
                    'name' => 'pass_reminder_custom_link',
                    'title' => 'Custom page for password reminder',
                    'comment' => 'Set your own URL to a password reset page. Useful when using a federated account management system.',
                ],
                [
                    'name' => 'registration_add_helptext_for_2_names',
                    'title' => 'Add helper to add two names in registration',
                    'comment' => 'Add help text for users to enter two names in the registration form when double lastnames are common.',
                ],
                [
                    'name' => 'allow_social_map_fields',
                    'title' => 'Users geolocation on a map',
                    'comment' => 'Enable the display of a map in the social network allowing you to locate other users. This includes several positions (current and destination) which have to be defined as addresses or coordinates in separate extra fields. The extra fields must be set as an array here.',
                ],
                [
                    'name' => 'allow_teachers_to_classes',
                    'title' => 'Allow teachers to manage classes',
                    'comment' => '',
                ],
                [
                    'name' => 'linkedin_organization_id',
                    'title' => 'LinkedIn Orgnization ID',
                    'comment' => "When sharing a badge on LinkedIn, LinkedIn allows you to set an organization ID that will link to the LinkedIn's page of your organization (to link the organization attributing the badge).",
                ],
                [
                    'name' => 'profile_fields_visibility',
                    'title' => 'Fields visible on profile page',
                    'comment' => "Array of fields and whether (boolean) they are visible or not on the user's profile page (also works with extra fields labels).",
                ],
                [
                    'name' => 'send_notification_when_user_added',
                    'title' => 'Send mail to admin when user created',
                    'comment' => 'Send email notification to admin when a user is created.',
                ],
                [
                    'name' => 'show_conditions_to_user',
                    'title' => 'Show specific registration conditions',
                    'comment' => "Show multiple conditions to user during sign up process. Provide an array with each element containing 'variable' (internal extra field name), 'display_text' (simple text for a checkbox), 'text_area' (long text of conditions).",
                ],
                [
                    'name' => 'user_import_settings',
                    'title' => 'Options for user import',
                    'comment' => 'Array of options to apply as default parameters in the CSV/XML user import.',
                ],
                [
                    'name' => 'user_search_on_extra_fields',
                    'title' => 'Search users by extra fields in users list for admins',
                    'comment' => 'Naturally include the given extra fields (array of extra fields labels) in the user searches.',
                ],
            ],
            'mail' => [
                [
                    'name' => 'allow_email_editor_for_anonymous',
                    'title' => 'E-mail editor for anonymous',
                    'comment' => 'Allow anonymous users to send e-mails from the platform. In this day and age of information security this is not a recommended option.',
                ],
                [
                    'name' => 'cron_notification_help_desk',
                    'title' => 'E-mail addresses to send cronjobs execution reports',
                    'comment' => 'Given as array of e-mail addresses. Does not work for all cronjobs yet.',
                ],
                [
                    'name' => 'mail_content_style',
                    'title' => 'Extra e-mail HTML body attributes',
                    'comment' => '',
                ],
                [
                    'name' => 'mail_header_style',
                    'title' => 'Extra e-mail HTML header attributes',
                    'comment' => '',
                ],
                [
                    'name' => 'messages_hide_mail_content',
                    'title' => 'Hide e-mail content to bring users to platform',
                    'comment' => 'Prefer short e-mail versions with a link to the messaging space on the platform to increase platform-based engagement.',
                ],
                [
                    'name' => 'notifications_extended_footer_message',
                    'title' => 'Extended notifications footer',
                    'comment' => 'Add a custom extra footer for notifications emails for a specific language, for example for privacy policy notices. Multiple languages and paragraphs can be added.',
                ],
                [
                    'name' => 'send_notification_score_in_percentage',
                    'title' => 'Send score in percentage in test results notification',
                    'comment' => '',
                ],
                [
                    'name' => 'send_two_inscription_confirmation_mail',
                    'title' => 'Send 2 registration e-mails',
                    'comment' => 'Send two separate e-mails on registration. One for the username, another one for the password.',
                ],
                [
                    'name' => 'show_user_email_in_notification',
                    'title' => "Show sender's e-mail address in notifications",
                    'comment' => '',
                ],
                [
                    'name' => 'update_users_email_to_dummy_except_admins',
                    'title' => 'Update users e-mail to dummy value during imports',
                    'comment' => 'During special CSV cron imports of users, automatically replace e-mails with dummy e-mail username@example.com.',
                ],
            ],
            'message' => [
                [
                    'name' => 'allow_user_message_tracking',
                    'title' => 'Admins can see personal messages',
                    'comment' => 'Allow administrators to see personal messages between a teacher and a learner. Please make sure you include a note in your terms and conditions as this might affect privacy protection.',
                ],
                [
                    'name' => 'filter_interactivity_messages',
                    'title' => 'Teachers can access learners messages only within session timeframe',
                    'comment' => 'Filter messages between a teacher and a learner between the session start end dates',
                ],
                [
                    'name' => 'private_messages_about_user',
                    'title' => 'Allow private messages between teachers about a learner',
                    'comment' => 'Allow exchange of messages from teachers/bosses about a user from the tracking page of that user.',
                ],
                [
                    'name' => 'private_messages_about_user_visible_to_user',
                    'title' => 'Allow learners to see messages about them between teachers',
                    'comment' => 'If exchange of messages about a user are enabled, this option will allow the corresponding user to see the messages. This is to comply with rules of transparency the organization may need to comply to.',
                ],
            ],
            'social' => [
                [
                    'name' => 'disable_dislike_option',
                    'title' => "Disable 'dislike' for social posts",
                    'comment' => 'Remove the thumb down option for social posts feedback. Only keep thumb up (like).',
                ],
                [
                    'name' => 'social_enable_messages_feedback',
                    'title' => 'Like/Dislike for social posts',
                    'comment' => 'Allows users to add feedback (likes or dislikes) to posts in social wall.',
                ],
                [
                    'name' => 'social_make_teachers_friend_all',
                    'title' => 'Teachers and admins see students as friends on social network',
                    'comment' => '',
                ],
                [
                    'name' => 'social_show_language_flag_in_profile',
                    'title' => 'Show language flag next to avatar in social network',
                    'comment' => '',
                ],
            ],
            'security' => [
                [
                    'name' => 'proxy_settings',
                    'title' => 'Proxy settings',
                    'comment' => 'Some features of Chamilo will connect to the exterior from the server. For example to make sure an external content exists when creating a link or showing an embedded page in the learning path. If your Chamilo server uses a proxy to get out of its network, this would be the place to configure it.',
                ],
                [
                    'name' => 'password_rotation_days',
                    'title' => 'Password rotation interval (days)',
                    'comment' => 'Number of days before users must rotate their password (0 = disabled).',
                ],
                [
                    'name' => 'password_requirements',
                    'title' => 'Minimal password syntax requirements',
                    'comment' => 'Defines the required structure for user passwords.',
                ],
                [
                    'name' => 'allow_online_users_by_status',
                    'title' => 'Filter users that can be seen as online',
                    'comment' => 'Limits online user visibility to specific user roles.',
                ],
                [
                    'name' => 'anonymous_autoprovisioning',
                    'title' => 'Auto-provision more anonymous users',
                    'comment' => 'Dynamically creates new anonymous users to support high visitor traffic.',
                ],
                [
                    'name' => 'admins_can_set_users_pass',
                    'title' => 'Admins can set users passwords manually',
                    'comment' => '',
                ],
                [
                    'name' => 'check_password',
                    'title' => 'Check password strength',
                    'comment' => '',
                ],
                [
                    'name' => 'security_block_inactive_users_immediately',
                    'title' => 'Block disabled users immediately',
                    'comment' => 'Immediately block users who have been disabled by the admin through users management. Otherwise, users who have been disabled will keep their previous privileges until they logout.',
                ],
                [
                    'name' => 'security_content_policy',
                    'title' => 'Content Security Policy',
                    'comment' => "Content Security Policy is an effective measure to protect your site from XSS attacks. By whitelisting sources of approved content, you can prevent the browser from loading malicious assets. This setting is particularly complicated to set with WYSIWYG editors, but if you add all domains that you want to authorize for iframes inclusion in the child-src statement, this example should work for you. You can prevent JavaScript from executing from external sources (including inside SVG images) by using a strict list in the 'script-src' argument. Leave blank to disable. Example setting: default-src 'self'; script-src 'self' 'unsafe-eval' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; child-src 'self' *.youtube.com yt.be *.vimeo.com *.slideshare.com;",
                ],
                [
                    'name' => 'security_content_policy_report_only',
                    'title' => 'Content Security Policy report only',
                    'comment' => 'This setting allows you to experiment by reporting but not enforcing some Content Security Policy.',
                ],
                [
                    'name' => 'security_public_key_pins',
                    'title' => 'HTTP Public Key Pinning',
                    'comment' => 'HTTP Public Key Pinning protects your site from MiTM attacks using rogue X.509 certificates. By whitelisting only the identities that the browser should trust, your users are protected in the event a certificate authority is compromised.',
                ],
                [
                    'name' => 'security_public_key_pins_report_only',
                    'title' => 'HTTP Public Key Pinning report only',
                    'comment' => 'This setting allows you to experiment by reporting but not enforcing some HTTP Public Key Pinning.',
                ],
                [
                    'name' => 'security_referrer_policy',
                    'title' => 'Security Referrer Policy',
                    'comment' => 'Referrer Policy is a new header that allows a site to control how much information the browser includes with navigation away from a document and should be set by all sites.',
                ],
                [
                    'name' => 'security_session_cookie_samesite_none',
                    'title' => 'Session cookie samesite',
                    'comment' => 'Enable samesite:None parameter for session cookie. More info: https://www.chromium.org/updates/same-site and https://developers.google.com/search/blog/2020/01/get-ready-for-new-samesitenone-secure',
                ],
                [
                    'name' => 'security_strict_transport',
                    'title' => 'HTTP Strict Transport Security',
                    'comment' => "HTTP Strict Transport Security is an excellent feature to support on your site and strengthens your implementation of TLS by getting the User Agent to enforce the use of HTTPS. Recommended value: 'strict-transport-security: max-age=63072000; includeSubDomains'. See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security. You can include the 'preload' suffix, but this has consequences on the top level domain (TLD), so probably not to be done lightly. See https://hstspreload.org/. Leave blank to disable.",
                ],
                [
                    'name' => 'security_x_content_type_options',
                    'title' => 'X-Content-Type-Options',
                    'comment' => "X-Content-Type-Options stops a browser from trying to MIME-sniff the content type and forces it to stick with the declared content-type. The only valid value for this header is 'nosniff'.",
                ],
                [
                    'name' => 'security_x_frame_options',
                    'title' => 'X-Frame-Options',
                    'comment' => "X-Frame-Options tells the browser whether you want to allow your site to be framed or not. By preventing a browser from framing your site you can defend against attacks like clickjacking. If defining a URL here, it should define the URL(s) from which your content should be visible, not the URLs from which your site accepts content. For example, if your main URL (root_web above) is https://11.chamilo.org/, then this setting should be: 'ALLOW-FROM https://11.chamilo.org'. These headers only apply to pages where Chamilo is responsible of the HTTP headers generation (i.e. '.php' files). It does not apply to static files. If playing with this feature, make sure you also update your web server configuration to add the right headers for static files. See CDN configuration documentation above (search for 'add_header') for more information. Recommended (strict) value for this setting, if enabled: 'SAMEORIGIN'.",
                ],
                [
                    'name' => 'security_xss_protection',
                    'title' => 'X-XSS-Protection',
                    'comment' => "X-XSS-Protection sets the configuration for the cross-site scripting filter built into most browsers. Recommended value '1; mode=block'.",
                ],
            ],
            'session' => [
                [
                    'name' => 'allow_career_diagram',
                    'title' => 'Enable career diagrams',
                    'comment' => 'Career diagrams allow you to display diagrams of careers, skills and courses.',
                ],
                [
                    'name' => 'show_all_sessions_on_my_course_page',
                    'title' => "Show all sessions on 'My courses' page",
                    'comment' => 'If enabled, this option show all sessions of the user in calendar-based view.',
                ],
                [
                    'name' => 'show_simple_session_info',
                    'title' => 'Show simple session info',
                    'comment' => "Add coach and dates to the session's subtitle in the sessions' list.",
                ],
                [
                    'name' => 'duplicate_specific_session_content_on_session_copy',
                    'title' => 'Enable the copy of session-specific content to another session',
                    'comment' => 'Allows duplication of resources that were created in the session when duplicating the session.',
                ],
                [
                    'name' => 'allow_delete_user_for_session_admin',
                    'title' => 'Session admins can delete users',
                    'comment' => '',
                ],
                [
                    'name' => 'allow_disable_user_for_session_admin',
                    'title' => 'Session admins can disable users',
                    'comment' => '',
                ],
                [
                    'name' => 'allow_redirect_to_session_after_inscription_about',
                    'title' => "Redirect to session after registration in session's 'About' page",
                    'comment' => '',
                ],
                [
                    'name' => 'allow_search_diagnostic',
                    'title' => 'Enable sessions search diagnosis',
                    'comment' => 'Allow tutors to get a diagnosis that will allow them to search for the best sessions for learners.',
                ],
                [
                    'name' => 'allow_session_admin_extra_access',
                    'title' => 'Session admin can access batch user import, update and export',
                    'comment' => '',
                ],
                [
                    'name' => 'allow_session_admin_login_as_teacher',
                    'title' => "Session admins can 'login as' teachers",
                    'comment' => '',
                ],
                [
                    'name' => 'allow_session_admin_read_careers',
                    'title' => 'Session admins can view careers',
                    'comment' => '',
                ],
                [
                    'name' => 'allow_user_session_collapsable',
                    'title' => 'Allow user to collapse sessions in My sessions',
                    'comment' => '',
                ],
                [
                    'name' => 'assignment_base_course_teacher_access_to_all_session',
                    'title' => 'Base course teacher can see assignments from all sessions',
                    'comment' => 'Show all learner publications (from base course and from all sessions) in the work/pending.php page of the base course.',
                ],
                [
                    'name' => 'default_session_list_view',
                    'title' => 'Default sessions list view',
                    'comment' => 'Select the default tab you want to see when opening the sessions list as admin.',
                ],
                [
                    'name' => 'email_template_subscription_to_session_confirmation_lost_password',
                    'title' => 'Add reset password link to e-mail notification of subscription to session',
                    'comment' => '',
                ],
                [
                    'name' => 'email_template_subscription_to_session_confirmation_username',
                    'title' => 'Add username to e-mail notification of subscription to session',
                    'comment' => '',
                ],
                [
                    'name' => 'hide_reporting_session_list',
                    'title' => 'Hide sessions list in reporting tool',
                    'comment' => 'Sessions that include the course are listed in the reporting tool inside the course itself, which can add considerable weight if the same course is used in hundreds of sessions. This option removes that list.',
                ],
                [
                    'name' => 'hide_search_form_in_session_list',
                    'title' => 'Hide search form in sessions list',
                    'comment' => '',
                ],
                [
                    'name' => 'hide_session_graph_in_my_progress',
                    'title' => 'Hide session chart in My progress',
                    'comment' => '',
                ],
                [
                    'name' => 'hide_tab_list',
                    'title' => 'Hide tabs on the session page',
                    'comment' => '',
                ],
                [
                    'name' => 'limit_session_admin_list_users',
                    'title' => 'Session admins are forbidden access to the users list',
                    'comment' => '',
                ],
                [
                    'name' => 'my_courses_session_order',
                    'title' => 'Change the default sorting of session in My sessions',
                    'comment' => "By default, sessions are ordered by start date. Change this by providing an array of type ['field' => 'end_date', 'order' => 'desc'].",
                ],
                [
                    'name' => 'my_progress_session_show_all_courses',
                    'title' => 'My progress: show course details in session',
                    'comment' => 'Display all details of each course in session when clicking on session details.',
                ],
                [
                    'name' => 'remove_session_url',
                    'title' => 'Hide link to session page',
                    'comment' => 'Hide link to the session page from the sessions list.',
                ],
                [
                    'name' => 'session_admins_access_all_content',
                    'title' => 'Session admins can access all course content',
                    'comment' => '',
                ],
                [
                    'name' => 'session_admins_edit_courses_content',
                    'title' => 'Session admins can edit course content',
                    'comment' => '',
                ],
                [
                    'name' => 'session_automatic_creation_user_id',
                    'title' => "Auto-created session's creator ID",
                    'comment' => "Set the user to use as creator of the automatically-created sessions (to avoid assigning every session to user '1' which is often the portal administrator).",
                ],
                [
                    'name' => 'session_classes_tab_disable',
                    'title' => 'Disable add class in session course for non-admin',
                    'comment' => 'Disable tab to add classes in session course for non-admins.',
                ],
                [
                    'name' => 'session_coach_access_after_duration_end',
                    'title' => 'Sessions by duration always available to coaches',
                    'comment' => 'Otherwise, session coaches only have access to sessions by duration during the active duration.',
                ],
                [
                    'name' => 'session_course_users_subscription_limited_to_session_users',
                    'title' => 'Limit subscriptions to course to only users of the session',
                    'comment' => 'Restrict the list of students to subscribe in the course session. And disable registration for users in all courses from Resume Session page.',
                ],
                [
                    'name' => 'session_courses_read_only_mode',
                    'title' => 'Set course read-only in session',
                    'comment' => "Let teachers set some courses in read-only mode when opened through sessions. In the course properties, check the 'Lock course in session' option.",
                ],
                [
                    'name' => 'session_creation_form_set_extra_fields_mandatory',
                    'title' => 'Set mandatory extra fields in session creation form',
                    'comment' => 'Require the listed fields during session creation.',
                ],
                [
                    'name' => 'session_creation_user_course_extra_field_relation_to_prefill',
                    'title' => 'Pre-fill session fields with user fields',
                    'comment' => "Array of relationships between user extra fields and session extra fields, so the session can be pre-filled with data matching the user's data.",
                ],
                [
                    'name' => 'session_import_settings',
                    'title' => 'Options for session import',
                    'comment' => 'Array of options to apply as default parameters in the CSV/XML session import.',
                ],
                [
                    'name' => 'session_list_order',
                    'title' => 'Sessions support manual sorting',
                    'comment' => '',
                ],
                [
                    'name' => 'session_list_show_count_users',
                    'title' => 'Show number of users in sessions list',
                    'comment' => 'The admin can see the number of users in each session. This adds additional weight to the sessions list, so if you use it often, consider carefully whether you want the extra waiting time.',
                ],
                [
                    'name' => 'session_model_list_field_ordered_by_id',
                    'title' => 'Sort session templates by id in session creation form',
                    'comment' => '',
                ],
                [
                    'name' => 'enable_auto_reinscription',
                    'title' => 'Enable Automatic Reinscription',
                    'comment' => 'Enable or disable automatic reinscription when course validity expires. The related cron job must also be activated.',
                ],
                [
                    'name' => 'enable_session_replication',
                    'title' => 'Enable Session Replication',
                    'comment' => 'Enable or disable automatic session replication. The related cron job must also be activated.',
                ],
                [
                    'name' => 'session_multiple_subscription_students_list_avoid_emptying',
                    'title' => 'Prevent emptying the subscribed users in session subscription',
                    'comment' => 'When using the multiple learners subscription to a session, prevent the normal behaviour which is to unsubscribe users who are not in the right panel when clicking submit. Keep all users there.',
                ],
                [
                    'name' => 'show_users_in_active_sessions_in_tracking',
                    'title' => 'Only display users from active sessions in tracking',
                    'comment' => '',
                ],
                [
                    'name' => 'tracking_columns',
                    'title' => 'Customize course-session tracking columns',
                    'comment' => "Define an array of columns for the following reports: 'course_session', 'my_students_lp', 'my_progress_lp', 'my_progress_courses'.",
                ],
                [
                    'name' => 'user_s_session_duration',
                    'title' => 'Auto-created sessions duration',
                    'comment' => 'Duration (in days) of the single-user, auto-created sessions. After expiry, the user cannot register to the same course (no other session is created).',
                ],
            ],
            'registration' => [
                [
                    'name' => 'allow_double_validation_in_registration',
                    'title' => 'Double validation for registration process',
                    'comment' => 'Simply display a confirmation request on the registration page before going forward with the user creation.',
                ],
                [
                    'name' => 'allow_fields_inscription',
                    'title' => 'Restrict fields shown during registration',
                    'comment' => "If you only want to show some of the available profile field, your can complete the array here with sub-elements 'fields' and 'extra_fields' containing arrays with a list of the fields to show.",
                ],
                [
                    'name' => 'required_extra_fields_in_inscription',
                    'title' => 'Required extra fields during registration',
                    'comment' => '',
                ],
                [
                    'name' => 'required_profile_fields',
                    'title' => 'Required fields during registration',
                    'comment' => '',
                ],
                [
                    'name' => 'send_inscription_msg_to_inbox',
                    'title' => 'Send the welcome message to e-mail and inbox',
                    'comment' => "By default, the welcome message (with credentials) is sent only by e-mail. Enable this option to send it to the user's Chamilo inbox as well.",
                ],
                [
                    'name' => 'hide_legal_accept_checkbox',
                    'title' => 'Hide legal accept checkbox in Terms and Conditions page',
                    'comment' => 'If set to true, removes the "I have read and accept" checkbox in the Terms and Conditions page flow.',
                ],
            ],
            'work' => [
                [
                    'name' => 'compilatio_tool',
                    'title' => 'Compilatio settings',
                    'comment' => 'Configure the Compilatio connection details here.',
                ],
                [
                    'name' => 'allow_compilatio_tool',
                    'title' => 'Enable Compilatio',
                    'comment' => 'Compilatio is an anti-cheating service that compares text between two submissions and reports if there is a high probability the content (usually assignments) is not genuine.',
                ],
                [
                    'name' => 'allow_my_student_publication_page',
                    'title' => 'Enable My assignments page',
                    'comment' => '',
                ],
                [
                    'name' => 'allow_only_one_student_publication_per_user',
                    'title' => 'Students can only upload one assignment',
                    'comment' => '',
                ],
                [
                    'name' => 'allow_redirect_to_main_page_after_work_upload',
                    'title' => 'Redirect to assigment tool homepage after upload or comment',
                    'comment' => 'Redirect to assignments list after uploading an assignment or a adding a comment',
                ],
                [
                    'name' => 'assignment_prevent_duplicate_upload',
                    'title' => 'Prevent duplicate uploads in assignments',
                    'comment' => '',
                ],
                [
                    'name' => 'block_student_publication_add_documents',
                    'title' => 'Prevent adding documents to assignments',
                    'comment' => '',
                ],
                [
                    'name' => 'block_student_publication_edition',
                    'title' => 'Prevent assignments edition',
                    'comment' => '',
                ],
                [
                    'name' => 'block_student_publication_score_edition',
                    'title' => 'Prevent teacher from modifying assignment scores',
                    'comment' => '',
                ],
                [
                    'name' => 'considered_working_time',
                    'title' => 'Enable time effort for assignments',
                    'comment' => 'This will allow teachers to give an estimated time effort (in hh:mm:ss format) to complete the assignment. Upon submission of the assignment and approval by the teacher (the assignment is given a score), the learner will automatically be assigned the corresponding time.',
                ],
                [
                    'name' => 'force_download_doc_before_upload_work',
                    'title' => 'Force download of document before assignment upload',
                    'comment' => 'Force users to download the provided document in the assignment definition before they can upload their assignment.',
                ],
                [
                    'name' => 'my_courses_show_pending_work',
                    'title' => "Display link to 'pending' assignments from My courses page",
                    'comment' => '',
                ],
            ],
            'skill' => [
                [
                    'name' => 'allow_private_skills',
                    'title' => 'Hide skills from learners',
                    'comment' => 'If enabled, skills can only be visible for admins, teachers (related to a user via a course), and HRM users (if related to a user).',
                ],
                [
                    'name' => 'allow_skill_rel_items',
                    'title' => 'Enable linking skills to items',
                    'comment' => 'This enables a major feature that enables any item to be linked to (and as such to allow acquisition of) a skill. The feature still requires the teacher to confirm the acquisition of the skill, so the acquisition is not automatic.',
                ],
                [
                    'name' => 'allow_teacher_access_student_skills',
                    'title' => "Allow teachers to access learners' skills",
                    'comment' => '',
                ],
                [
                    'name' => 'badge_assignation_notification',
                    'title' => 'Send notification to learner when a skill/badge has been acquired',
                    'comment' => '',
                ],
                [
                    'name' => 'hide_skill_levels',
                    'title' => 'Hide skill levels feature',
                    'comment' => '',
                ],
                [
                    'name' => 'skill_levels_names',
                    'title' => 'Skill levels names',
                    'comment' => 'Define names for levels of skills as an array of id => name.',
                ],
                [
                    'name' => 'skills_hierarchical_view_in_user_tracking',
                    'title' => 'Show skills as a hierarchical table',
                    'comment' => '',
                ],
                [
                    'name' => 'skills_teachers_can_assign_skills',
                    'title' => 'Allow teachers to set which skills are acquired through their courses',
                    'comment' => 'By default, only admins can decide which skills can be acquired through which course.',
                ],
                [
                    'name' => 'manual_assignment_subskill_autoload',
                    'title' => 'Assigning skills to user: sub-skills auto-loading',
                    'comment' => 'When manually assigning skills to a user, the form can be set to automatically offer you to assign a sub-skill instead of the skill you selected.',
                ],
            ],
            'survey' => [
                [
                    'name' => 'show_pending_survey_in_menu',
                    'title' => 'Show "Pending surveys" in menu',
                    'comment' => 'Display a menu item that lets users access their pending surveys.',
                ],
                [
                    'name' => 'hide_survey_edition',
                    'title' => 'Prevent survey edition',
                    'comment' => 'Prevent editing surveys for all surveys listed here (by code). Use * to prevent edition of all surveys.',
                ],
                [
                    'name' => 'hide_survey_reporting_button',
                    'title' => 'Hide survey reporting button',
                    'comment' => 'Allows admins to hide survey reporting button if surveys are used to survey teachers.',
                ],
                [
                    'name' => 'show_surveys_base_in_sessions',
                    'title' => 'Display surveys from base course in all session courses',
                    'comment' => '',
                ],
                [
                    'name' => 'survey_additional_teacher_modify_actions',
                    'title' => 'Add additional actions (as links) to survey lists for teachers',
                    'comment' => "Add actions (usually connected to plugins) in the list of surveys. Use array syntax ['myplugin' => ['MyPlugin', 'urlGeneratorCallback']].",
                ],
                [
                    'name' => 'survey_allow_answered_question_edit',
                    'title' => 'Allow teachers to edit survey questions after students answered',
                    'comment' => '',
                ],
                [
                    'name' => 'survey_anonymous_show_answered',
                    'title' => 'Allow teachers to see who answered in anonymous surveys',
                    'comment' => 'Allow teachers to see which learners have already answered an anonymous survey. This only appears once more than one user has answered, so it remains difficult to identify who answered what.',
                ],
                [
                    'name' => 'survey_backwards_enable',
                    'title' => "Enable 'previous question' button in surveys",
                    'comment' => '',
                ],
                [
                    'name' => 'survey_duplicate_order_by_name',
                    'title' => 'Order by student name when using survey duplication feature',
                    'comment' => "The survey duplication feature is oriented towards teachers and is meant to ask teachers to give their appreciation about each student in order. This option will order the questions by learner's lastname.",
                ],
                [
                    'name' => 'survey_mark_question_as_required',
                    'title' => "Mark all survey questions as 'required' by default",
                    'comment' => '',
                ],
            ],
            'ticket' => [
                [
                    'name' => 'ticket_project_user_roles',
                    'title' => 'Access by role to ticket projects',
                    'comment' => "Allow ticket projects to be accesses by specific user roles. Example: ['permissions' => [1 => [17]] where project_id = 1, STUDENT_BOSS = 17.",
                ],
            ],
            'ai_helpers' => [
                [
                    'name' => 'enable_ai_helpers',
                    'title' => 'Enable the AI helper tool',
                    'comment' => 'Enables all available AI-powered features in the platform.',
                ],
                [
                    'name' => 'ai_providers',
                    'title' => 'AI providers connection data',
                    'comment' => 'Configuration data to connect with external AI services.',
                ],
                [
                    'name' => 'learning_path_generator',
                    'title' => 'Learning paths generator',
                    'comment' => 'Generates personalized learning paths using AI suggestions.',
                ],
                [
                    'name' => 'exercise_generator',
                    'title' => 'Exercise generator',
                    'comment' => 'Generates personalized tests with AI based on course content.',
                ],
                [
                    'name' => 'open_answers_grader',
                    'title' => 'Open answers grader',
                    'comment' => 'Automatically grades open-ended answers using AI.',
                ],
                [
                    'name' => 'tutor_chatbot',
                    'title' => 'Tutor chatbot energized by AI',
                    'comment' => 'Provides students with an AI-powered tutoring assistant.',
                ],
                [
                    'name' => 'task_grader',
                    'title' => 'Assignments grader',
                    'comment' => 'Uses AI to evaluate and grade uploaded assignments.',
                ],
                [
                    'name' => 'content_analyser',
                    'title' => 'Content analyser',
                    'comment' => 'Analyses learning materials to extract insights or improve quality.',
                ],
                [
                    'name' => 'image_generator',
                    'title' => 'Image generator',
                    'comment' => 'Generates images based on prompts or content using AI.',
                ],
                [
                    'name' => 'glossary_terms_generator',
                    'title' => 'Glossary terms generator',
                    'comment' => 'Allow teachers to ask for AI-generated glossary terms in their course. This will generate 20 terms based on the course title and the general description in the course description tool. If used more than once, it will exclude terms already present in that glossary (make sure content can be shared with the configured AI services).',
                ],
                [
                    'name' => 'video_generator',
                    'title' => 'Video generator',
                    'comment' => 'Generates videos based on prompts or content using AI (this might consume many tokens).',
                ],
                [
                    'name' => 'course_analyser',
                    'title' => 'Course analyser',
                    'comment' => 'Analyses all resources in one or many courses and pre-trains the AI model to answer any question on this or these courses (make sure content can be shared with the configured AI services).',
                ],
                [
                    'name' => 'disclose_ai_assistance',
                    'title' => 'Disclose AI assistance',
                    'comment' => 'Show a tag on any content or feedback that has been generated or co-generated by any AI system, evidencing to the user that the content was built with the help of some AI system. Details about which AI system was used in which case are kept inside the database for audit, but are not directly accessible by the final user.',
                ],
            ],
            'privacy' => [
                [
                    'name' => 'data_protection_officer_email',
                    'title' => 'Data protection officer e-mail address',
                    'comment' => '',
                ],
                [
                    'name' => 'data_protection_officer_name',
                    'title' => 'Data protection officer name',
                    'comment' => '',
                ],
                [
                    'name' => 'data_protection_officer_role',
                    'title' => 'Data protection officer role',
                    'comment' => '',
                ],
                [
                    'name' => 'hide_user_field_from_list',
                    'title' => 'Hide fields from users list in course',
                    'comment' => 'By default, we show all data from users in the users tool in the course. This array allows you to specify which fields you do not want to display. Only affects main fields (not extra fields).',
                ],
                [
                    'name' => 'disable_gdpr',
                    'title' => 'Disable GDPR features',
                    'comment' => 'If you already manage your personal data protection declaration to users elsewhere, you can safely disable this feature.',
                ],
                [
                    'name' => 'disable_change_user_visibility_for_public_courses',
                    'title' => 'Disable making tool users visible in public courses',
                    'comment' => "Avoid anyone making the 'users' tool visible in a public course.",
                ],
            ],
            'workflows' => [
                [
                    'name' => 'plugin_redirection_enabled',
                    'title' => 'Enable redirection plugin',
                    'comment' => 'Enable only if you are using the Redirection plugin',
                ],
                [
                    'name' => 'usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe',
                    'title' => 'Disable user unsubscription from course/session on user unsubscription from group/class',
                    'comment' => '',
                ],
                [
                    'name' => 'usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe',
                    'title' => 'Disable user unsubscription from course on course removal from group/class',
                    'comment' => '',
                ],
                [
                    'name' => 'usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe',
                    'title' => 'Disable user unsubscription from session on session removal from group/class',
                    'comment' => '',
                ],
                [
                    'name' => 'drh_allow_access_to_all_students',
                    'title' => 'HRM can access all students from reporting pages',
                    'comment' => '',
                ],
                [
                    'name' => 'send_all_emails_to',
                    'title' => 'Send all e-mails to',
                    'comment' => 'Give a list of e-mail addresses to whom *all* e-mails sent from the platform will be sent. The e-mails are sent to these addresses as a visible destination.',
                ],
                [
                    'name' => 'go_to_course_after_login',
                    'title' => 'Go directly to the course after login',
                    'comment' => 'When a user is registered in one course, go directly to the course after login',
                ],
                [
                    'name' => 'allow_users_to_create_courses',
                    'title' => 'Allow non admin to create courses',
                    'comment' => 'Allow non administrators (teachers) to create new courses on the server',
                ],
                [
                    'name' => 'allow_user_course_subscription_by_course_admin',
                    'title' => 'Allow User Course Subscription By Course Admininistrator',
                    'comment' => 'Activate this option will allow course administrator to subscribe users inside a course',
                ],
                [
                    'name' => 'teacher_can_select_course_template',
                    'title' => 'Teacher can select a course as template',
                    'comment' => 'Allow pick a course as template for the new course that teacher is creating',
                ],
                [
                    'name' => 'disabled_edit_session_coaches_course_editing_course',
                    'title' => 'Disable the ability to edit course coaches',
                    'comment' => 'When disabled, admins do not have a link to quickly assign coaches to session-courses on the course edition page.',
                ],
                [
                    'name' => 'course_visibility_change_only_admin',
                    'title' => 'Course visibility changes for admins only',
                    'comment' => 'Remove the possibility for non-admins to change the course visibility. Visibility can be an issue when there are too many teachers to control directly. Forcing visibilities allows the organization to better manage courses catalogues.',
                ],
                [
                    'name' => 'multiple_url_hide_disabled_settings',
                    'title' => 'Hide disabled settings in sub-URLs',
                    'comment' => 'Set to yes to hide settings completely in a sub-URL if the setting is disabled in the main URL (where the access_url_changeable field = 0)',
                ],
                [
                    'name' => 'gamification_mode',
                    'title' => 'Gamification mode',
                    'comment' => 'Activate the stars achievement in learning paths',
                ],
                [
                    'name' => 'load_term_conditions_section',
                    'title' => 'Load term conditions section',
                    'comment' => 'The legal agreement will appear during the login or when enter to a course.',
                ],
                [
                    'name' => 'update_student_expiration_x_date',
                    'title' => 'Set expiration date on first login',
                    'comment' => "Array defining the 'days' and 'months' to set the account expiration date when the user first logs in.",
                ],
                [
                    'name' => 'user_number_of_days_for_default_expiration_date_per_role',
                    'title' => 'Default expiration days by role',
                    'comment' => 'An array of role => number which represents the number of days an account has before expiration, depending on the role.',
                ],
                [
                    'name' => 'user_edition_extra_field_to_check',
                    'title' => 'Set an extra field as trigger for registration as ex-learner',
                    'comment' => 'Give an extra field label here. If this extra field is updated for any user, a process is triggered to check the access to this user to courses with the same given extra field.',
                ],
                [
                    'name' => 'allow_working_time_edition',
                    'title' => 'Enable edition of course work time',
                    'comment' => 'Enable this feature to let teachers manually update the time spent in the course by learners.',
                ],
                [
                    'name' => 'disable_user_conditions_sender_id',
                    'title' => 'Internal ID of the user used to send disabled account notifications',
                    'comment' => "Avoid being too personal with users by using a 'bot' account to send e-mails to users when their account is disabled for some reason.",
                ],
                [
                    'name' => 'redirect_index_to_url_for_logged_users',
                    'title' => 'Redirect index.php to given URL for authenticated users',
                    'comment' => 'If you do not want to use the index page (announcements, popular courses, etc), you can define here the script (from the document root) where users will be redirected when trying to load the index.',
                ],
                [
                    'name' => 'default_menu_entry_for_course_or_session',
                    'title' => 'Default menu entry for courses',
                    'comment' => "Define the default sub-elements of the 'Courses' entry to display if user is not registered to any course nor session.",
                ],
                [
                    'name' => 'session_admin_user_subscription_search_extra_field_to_search',
                    'title' => 'Extra user field used to search and name sessions',
                    'comment' => 'This setting defines the extra user field key (e.g., "company") that will be used to search for users and to define the name of the session when registering students from /admin-dashboard/register.',
                ],
            ],
        ];
    }
}
