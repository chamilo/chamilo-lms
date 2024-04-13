<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class SettingsCurrentFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['settings-update'];
    }

    public function load(ObjectManager $manager): void
    {
        $existingSettings = self::getExistingSettings();
        $newConfigurationSettings = self::getNewConfigurationSettings();

        foreach (array_merge($existingSettings, $newConfigurationSettings) as $settingData) {
            $setting = $manager->getRepository(SettingsCurrent::class)->findOneBy(['variable' => $settingData['name']]);

            if (!$setting) {
                continue;
            }

            $setting->setTitle($settingData['title']);
            $setting->setComment($settingData['comment']);

            $manager->persist($setting);
        }

        $manager->flush();
    }

    public static function getExistingSettings(): array
    {
        return [
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
                'name' => 'site_name',
                'title' => 'E-learning portal name',
                'comment' => 'The Name of your Chamilo Portal (appears in the header)',
            ],
            [
                'name' => 'administrator_email',
                'title' => 'Portal Administrator: e-mail',
                'comment' => 'The e-mail address of the Platform Administrator (appears in the footer on the left)',
            ],
            [
                'name' => 'administrator_surname',
                'title' => 'Portal Administrator: Last Name',
                'comment' => 'The Family Name of the Platform Administrator (appears in the footer on the left)',
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
                'name' => 'show_administrator_data',
                'title' => 'Platform Administrator Information in footer',
                'comment' => 'Show the Information of the Platform Administrator in the footer?',
            ],
            [
                'name' => 'show_tutor_data',
                'title' => "Session's tutor's data is shown in the footer.",
                'comment' => "Show the session's tutor reference (name and e-mail if available) in the footer?",
            ],
            [
                'name' => 'show_teacher_data',
                'title' => 'Show teacher information in footer',
                'comment' => 'Show the teacher reference (name and e-mail if available) in the footer?',
            ],
            [
                'name' => 'homepage_view',
                'title' => 'Course homepage layout',
                'comment' => 'How would you like the homepage of a course to look (icons layout)?',
            ],
            [
                'name' => 'show_toolshortcuts',
                'title' => 'Tools shortcuts',
                'comment' => 'Show the tool shortcuts in the banner?',
            ],
            [
                'name' => 'allow_group_categories',
                'title' => 'Group categories',
                'comment' => 'Allow teachers to create categories in the Groups tool?',
            ],
            [
                'name' => 'server_type',
                'title' => 'Server Type',
                'comment' => 'What sort of server is this? This enables or disables some specific options. On a development server there is a translation feature functional that inidcates untranslated strings',
            ],
            [
                'name' => 'platformLanguage',
                'title' => 'Portal Language',
                'comment' => 'You can determine the platform languages in a different part of the platform administration, namely: <a href="languages.php">Chamilo Platform Languages</a>',
            ],
            [
                'name' => 'showonline',
                'title' => "Who's Online",
                'comment' => 'Display the number of persons that are online?',
            ],
            [
                'name' => 'profile',
                'title' => 'Profile',
                'comment' => 'Which parts of the profile can be changed?',
            ],
            [
                'name' => 'default_document_quotum',
                'title' => 'Default hard disk space',
                'comment' => 'What is the available disk space for a course? You can override the quota for specific course through: platform administration > Courses > modify',
            ],
            [
                'name' => 'registration',
                'title' => 'Registration: required fields',
                'comment' => 'Which fields are required (besides name, first name, login and password)',
            ],
            [
                'name' => 'default_group_quotum',
                'title' => 'Group disk space available',
                'comment' => 'What is the default hard disk spacde available for a groups documents tool?',
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
                'name' => 'allow_lostpassword',
                'title' => 'Lost password',
                'comment' => 'Are users allowed to request their lost password?',
            ],
            [
                'name' => 'allow_user_headings',
                'title' => 'Allow users profiling inside courses',
                'comment' => 'Can a teacher define learner profile fields to retrieve additional information?',
            ],
            [
                'name' => 'course_create_active_tools',
                'title' => 'Modules active upon course creation',
                'comment' => 'Which tools have to be enabled (visible) by default when a new course is created?',
            ],
            [
                'name' => 'allow_personal_agenda',
                'title' => 'Personal Agenda',
                'comment' => 'Can the learner add personal events to the Agenda?',
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
                'name' => 'permanently_remove_deleted_files',
                'title' => 'Deleted files cannot be restored',
                'comment' => 'Deleting a file in the documents tool permanently deletes it. The file cannot be restored',
            ],
            [
                'name' => 'dropbox_allow_overwrite',
                'title' => 'Dropbox: Can documents be overwritten',
                'comment' => 'Can the original document be overwritten when a user or trainer uploads a document with the name of a document that already exist? If you answer yes then you loose the versioning mechanism.',
            ],
            [
                'name' => 'dropbox_max_filesize',
                'title' => 'Dropbox: Maximum file size of a document',
                'comment' => 'How big (in MB) can a dropbox document be?',
            ],
            [
                'name' => 'dropbox_allow_just_upload',
                'title' => 'Dropbox: Upload to own dropbox space?',
                'comment' => 'Allow trainers and users to upload documents to their dropbox without sending  the documents to themselves',
            ],
            [
                'name' => 'dropbox_allow_student_to_student',
                'title' => 'Dropbox: Learner <-> Learner',
                'comment' => 'Allow users to send documents to other users (peer 2 peer). Users might use this for less relevant documents also (mp3, tests solutions, ...). If you disable this then the users can send documents to the trainer only.',
            ],
            [
                'name' => 'dropbox_allow_group',
                'title' => 'Dropbox: allow group',
                'comment' => 'Users can send files to groups',
            ],
            [
                'name' => 'dropbox_allow_mailing',
                'title' => 'Dropbox: Allow mailing',
                'comment' => 'With the mailing functionality you can send each learner a personal document',
            ],
            [
                'name' => 'administratorTelephone',
                'title' => 'Portal Administrator: Telephone',
                'comment' => 'The telephone number of the platform administrator',
            ],
            [
                'name' => 'extended_profile',
                'title' => 'Portfolio',
                'comment' => "If this setting is on, a user can fill in the following (optional) fields: 'My personal open area', 'My competences', 'My diplomas', 'What I am able to teach'",
            ],
            [
                'name' => 'student_view_enabled',
                'title' => 'Enable learner view',
                'comment' => 'Enable the learner view, which allows a teacher or admin to see a course as a learner would see it',
            ],
            [
                'name' => 'show_navigation_menu',
                'title' => 'Display course navigation menu',
                'comment' => 'Display a navigation menu that quickens access to the tools',
            ],
            [
                'name' => 'enable_tool_introduction',
                'title' => 'Enable tool introduction',
                'comment' => "Enable introductions on each tool's homepage",
            ],
            [
                'name' => 'page_after_login',
                'title' => 'Page after login',
                'comment' => 'The page which is seen by the user entering the platform',
            ],
            [
                'name' => 'time_limit_whosonline',
                'title' => 'Time limit on Who Is Online',
                'comment' => 'This time limit defines for how many minutes after his last action a user will be considered *online*',
            ],
            [
                'name' => 'breadcrumbs_course_homepage',
                'title' => 'Course homepage breadcrumb',
                'comment' => "The breadcrumb is the horizontal links navigation system usually in the top left of your page. This option selects what you want to appear in the breadcrumb on courses' homepages",
            ],
            [
                'name' => 'example_material_course_creation',
                'title' => 'Example material on course creation',
                'comment' => 'Create example material automatically when creating a new course',
            ],
            [
                'name' => 'account_valid_duration',
                'title' => 'Account validity',
                'comment' => 'A user account is valid for this number of days after creation',
            ],
            [
                'name' => 'use_session_mode',
                'title' => 'Use training sessions',
                'comment' => 'Training sessions give a different way of dealing with training, where courses have an author, a coach and learners. Each coach gives a course for a set period of time, called a *training session*, to a set of learners who do not mix with other learner groups attached to another training session.',
            ],
            [
                'name' => 'allow_email_editor',
                'title' => 'Online e-mail editor enabled',
                'comment' => 'If this option is activated, clicking on an e-mail address will open an online editor.',
            ],
            [
                'name' => 'show_email_addresses',
                'title' => 'Show email addresses',
                'comment' => 'Show email addresses to users',
            ],
            [
                'name' => 'upload_extensions_list_type',
                'title' => 'Type of filtering on document uploads',
                'comment' => 'Whether you want to use the blacklist or whitelist filtering. See blacklist or whitelist description below for more details.',
            ],
            [
                'name' => 'upload_extensions_blacklist',
                'title' => 'Blacklist - setting',
                'comment' => "The blacklist is used to filter the files extensions by removing (or renaming) any file which extension figures in the blacklist below. The extensions should figure without the leading dot (.) and separated by semi-column (;) like the following:  exe;com;bat;scr;php. Files without extension are accepted. Letter casing (uppercase/lowercase) doesn't matter.",
            ],
            [
                'name' => 'upload_extensions_whitelist',
                'title' => 'Whitelist - setting',
                'comment' => "The whitelist is used to filter the file extensions by removing (or renaming) any file whose extension does *NOT* figure in the whitelist below. It is generally considered as a safer but more restrictive approach to filtering. The extensions should figure without the leading dot (.) and separated by semi-column (;) such as the following:  htm;html;txt;doc;xls;ppt;jpg;jpeg;gif;sxw. Files without extension are accepted. Letter casing (uppercase/lowercase) doesn't matter.",
            ],
            [
                'name' => 'upload_extensions_skip',
                'title' => 'Filtering behaviour (skip/rename)',
                'comment' => "If you choose to skip, the files filtered through the blacklist or whitelist will not be uploaded to the system. If you choose to rename them, their extension will be replaced by the one defined in the extension replacement setting. Beware that renaming doesn't really protect you, and may cause name collision if several files of the same name but different extensions exist.",
            ],
            [
                'name' => 'upload_extensions_replace_by',
                'title' => 'Replacement extension',
                'comment' => 'Enter the extension that you want to use to replace the dangerous extensions detected by the filter. Only needed if you have selected a filter by replacement.',
            ],
            [
                'name' => 'show_number_of_courses',
                'title' => 'Show courses number',
                'comment' => 'Show the number of courses in each category in the courses categories on the homepage',
            ],
            [
                'name' => 'show_empty_course_categories',
                'title' => 'Show empty courses categories',
                'comment' => "Show the categories of courses on the homepage, even if they're empty",
            ],
            [
                'name' => 'show_back_link_on_top_of_tree',
                'title' => 'Show back links from categories/courses',
                'comment' => 'Show a link to go back in the courses hierarchy. A link is available at the bottom of the list anyway.',
            ],
            [
                'name' => 'show_different_course_language',
                'title' => 'Show course languages',
                'comment' => 'Show the language each course is in, next to the course title, on the homepage courses list',
            ],
            [
                'name' => 'split_users_upload_directory',
                'title' => "Split users' upload directory",
                'comment' => "On high-load portals, where a lot of users are registered and send their pictures, the upload directory (main/upload/users/) might contain too many files for the filesystem to handle (it has been reported with more than 36000 files on a Debian server). Changing this option will enable a one-level splitting of the directories in the upload directory. 9 directories will be used in the base directory and all subsequent users' directories will be stored into one of these 9 directories. The change of this option will not affect the directories structure on disk, but will affect the behaviour of the Chamilo code, so if you change this option, you have to create the new directories and move the existing directories by yourself on te server. Be aware that when creating and moving those directories, you will have to move the directories of users 1 to 9 into subdirectories of the same name. If you are not sure about this option, it is best not to activate it.",
            ],
            [
                'name' => 'hide_dltt_markup',
                'title' => 'Hide DLTT Markup',
                'comment' => 'Hide the [= ... =] markup when a language variable is not translated',
            ],
            [
                'name' => 'display_categories_on_homepage',
                'title' => 'Display categories on home page',
                'comment' => 'This option will display or hide courses categories on the portal home page',
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
                'name' => 'show_tabs',
                'title' => 'Tabs in the header',
                'comment' => 'Check the tabs you want to see appear in the header. The unchecked tabs will appear on the right hand menu on the portal homepage and my courses page if these need to appear',
            ],
            [
                'name' => 'default_forum_view',
                'title' => 'Default forum view',
                'comment' => 'What should be the default option when creating a new forum. Any trainer can however choose a different view for every individual forum',
            ],
            [
                'name' => 'platform_charset',
                'title' => 'Character set',
                'comment' => 'The character set is what pilots the way specific languages can be displayed in Chamilo. If you use Russian or Japanese characters, for example, you might want to change this. For all english, latin and west-european characters, the default UTF-8 should be alright.',
            ],
            [
                'name' => 'noreply_email_address',
                'title' => 'No-reply e-mail address',
                'comment' => 'This is the e-mail address to be used when an e-mail has to be sent specifically requesting that no answer be sent in return. Generally, this e-mail address should be configured on your server to drop/ignore any incoming e-mail.',
            ],
            [
                'name' => 'survey_email_sender_noreply',
                'title' => 'Survey e-mail sender (no-reply)',
                'comment' => 'Should the survey invitations use the coach e-mail address or the no-reply address defined in the main configuration section?',
            ],
            [
                'name' => 'openid_authentication',
                'title' => 'OpenID authentication',
                'comment' => 'Enable the OpenID URL-based authentication (displays an additional login form on the homepage)',
            ],
            [
                'name' => 'gradebook_enable',
                'title' => 'Assessments tool activation',
                'comment' => 'The Assessments tool allows you to assess competences in your organization by merging classroom and online activities evaluations into Performance reports. Do you want to activate it?',
            ],
            [
                'name' => 'gradebook_score_display_coloring',
                'title' => 'Grades thresholds colouring',
                'comment' => 'Tick the box to enable marks thresholds',
            ],
            [
                'name' => 'gradebook_score_display_custom',
                'title' => 'Competence levels labelling',
                'comment' => 'Tick the box to enable Competence levels labelling',
            ],
            [
                'name' => 'gradebook_score_display_colorsplit',
                'title' => 'Threshold',
                'comment' => 'The threshold (in %) under which scores will be colored red',
            ],
            [
                'name' => 'gradebook_score_display_upperlimit',
                'title' => 'Display score upper limit',
                'comment' => "Tick the box to show the score's upper limit",
            ],
            [
                'name' => 'gradebook_number_decimals',
                'title' => 'Number of decimals',
                'comment' => 'Allows you to set the number of decimals allowed in a score',
            ],
            [
                'name' => 'user_selected_theme',
                'title' => 'User theme selection',
                'comment' => 'Allow users to select their own visual theme in their profile. This will change the look of Chamilo for them, but will leave the default style of the portal intact. If a specific course or session has a specific theme assigned, it will have priority over user-defined themes.',
            ],
            [
                'name' => 'allow_course_theme',
                'title' => 'Allow course themes',
                'comment' => "Allows course graphical themes and makes it possible to change the style sheet used by a course to any of the possible style sheets available to Chamilo. When a user enters the course, the style sheet of the course will have priority over the user's own style sheet and the platform's default style sheet.",
            ],
            [
                'name' => 'show_closed_courses',
                'title' => 'Display closed courses on login page and portal start page?',
                'comment' => "Display closed courses on the login page and courses start page? On the portal start page an icon will appear next to the courses to quickly subscribe to each courses. This will only appear on the portal's start page when the user is logged in and when the user is not subscribed to the portal yet.",
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
                'name' => 'add_users_by_coach',
                'title' => 'Register users by Coach',
                'comment' => 'Coach users may create users to the platform and subscribe users to a session.',
            ],
            [
                'name' => 'extend_rights_for_coach',
                'title' => 'Extend rights for coach',
                'comment' => 'Activate this option will give the coach the same permissions as the trainer on authoring tools',
            ],
            [
                'name' => 'extend_rights_for_coach_on_survey',
                'title' => 'Extend rights for coachs on surveys',
                'comment' => 'Activate this option will allow the coachs to create and edit surveys',
            ],
            [
                'name' => 'show_session_coach',
                'title' => 'Show session coach',
                'comment' => 'Show the global session coach name in session title box in the courses list',
            ],
            [
                'name' => 'allow_users_to_create_courses',
                'title' => 'Allow non admin to create courses',
                'comment' => 'Allow non administrators (teachers) to create new courses on the server',
            ],
            [
                'name' => 'allow_message_tool',
                'title' => 'Internal messaging tool',
                'comment' => 'Enabling the internal messaging tool allows users to send messages to other users of the platform and to have a messaging inbox.',
            ],
            [
                'name' => 'allow_social_tool',
                'title' => 'Social network tool (Facebook-like)',
                'comment' => 'The social network tool allows users to define relations with other users and, by doing so, to define groups of friends. Combined with the internal messaging tool, this tool allows tight communication with friends, inside the portal environment.',
            ],
            [
                'name' => 'allow_students_to_browse_courses',
                'title' => 'Learners access to courses catalogue',
                'comment' => 'Allow learners to browse the courses catalogue and subscribe to available courses',
            ],
            [
                'name' => 'show_session_data',
                'title' => 'Show session data title',
                'comment' => 'Show session data comment',
            ],
            [
                'name' => 'allow_use_sub_language',
                'title' => 'Allow definition and use of sub-languages',
                'comment' => "By enabling this option, you will be able to define variations for each of the language terms used in the platform's interface, in the form of a new language based on and extending an existing language. You'll find this option in the languages section of the administration panel.",
            ],
            [
                'name' => 'show_glossary_in_documents',
                'title' => 'Show glossary terms in documents',
                'comment' => 'From here you can configure how to add links to the glossary terms from the documents',
            ],
            [
                'name' => 'allow_terms_conditions',
                'title' => 'Enable terms and conditions',
                'comment' => 'This option will display the Terms and Conditions in the register form for new users. Need to be configured first in the portal administration page.',
            ],
            [
                'name' => 'search_enabled',
                'title' => 'Full-text search feature',
                'comment' => 'Select "Yes" to enable this feature. It is highly dependent on the Xapian extension for PHP, so this will not work if this extension is not installed on your server, in version 1.x at minimum.',
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
            [
                'name' => 'show_courses_descriptions_in_catalog',
                'title' => 'Show the courses descriptions in the catalog',
                'comment' => 'Show the courses descriptions as an integrated popup when clicking on a course info icon in the courses catalog',
            ],
            [
                'name' => 'allow_coach_to_edit_course_session',
                'title' => 'Allow coaches to edit inside course sessions',
                'comment' => 'Allow coaches to edit inside course sessions',
            ],
            [
                'name' => 'show_glossary_in_extra_tools',
                'title' => 'Show the glossary terms in extra tools',
                'comment' => 'From here you can configure how to add the glossary terms in extra tools as learning path and exercice tool',
            ],
            [
                'name' => 'send_email_to_admin_when_create_course',
                'title' => 'E-mail alert on course creation',
                'comment' => 'Send an email to the platform administrator each time a teacher creates a new course',
            ],
            [
                'name' => 'go_to_course_after_login',
                'title' => 'Go directly to the course after login',
                'comment' => 'When a user is registered in one course, go directly to the course after login',
            ],
            [
                'name' => 'math_asciimathML',
                'title' => 'ASCIIMathML mathematical editor',
                'comment' => 'Enable ASCIIMathML mathematical editor',
            ],
            [
                'name' => 'enabled_asciisvg',
                'title' => 'Enable AsciiSVG',
                'comment' => 'Enable the AsciiSVG plugin in the WYSIWYG editor to draw charts from mathematical functions.',
            ],
            [
                'name' => 'include_asciimathml_script',
                'title' => 'Load the Mathjax library in all the system pages',
                'comment' => 'Activate this setting if you want to show MathML-based mathematical formulas and ASCIIsvg-based mathematical graphics not only in the "Documents" tool, but elsewhere in the system.',
            ],
            [
                'name' => 'youtube_for_students',
                'title' => 'Allow learners to insert videos from YouTube',
                'comment' => 'Enable the possibility that learners can insert Youtube videos',
            ],
            [
                'name' => 'block_copy_paste_for_students',
                'title' => 'Block learners copy and paste',
                'comment' => 'Block learners the ability to copy and paste into the WYSIWYG editor',
            ],
            [
                'name' => 'more_buttons_maximized_mode',
                'title' => 'Buttons bar extended',
                'comment' => 'Enable button bars extended when the WYSIWYG editor is maximized',
            ],
            [
                'name' => 'students_download_folders',
                'title' => 'Allow learners to download directories',
                'comment' => 'Allow learners to pack and download a complete directory from the document tool',
            ],
            [
                'name' => 'users_copy_files',
                'title' => 'Allow users to copy files from a course in your personal file area',
                'comment' => 'Allows users to copy files from a course in your personal file area, visible through the Social Network or through the HTML editor when they are out of a course',
            ],
            [
                'name' => 'allow_students_to_create_groups_in_social',
                'title' => 'Allow learners to create groups in social network',
                'comment' => 'Allow learners to create groups in social network',
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
            [
                'name' => 'use_users_timezone',
                'title' => 'Enable users timezones',
                'comment' => 'Enable the possibility for users to select their own timezone. The timezone field should be set to visible and changeable in the Profiling menu in the administration section before users can choose their own. Once configured, users will be able to see assignment deadlines and other time references in their own timezone, which will reduce errors at delivery time.',
            ],
            [
                'name' => 'timezone_value',
                'title' => 'Timezone value',
                'comment' => "The timezone for this portal should be set to the same timezone as the organization's headquarter. If left empty, it will use the server's timezone.<br />If configured, all times on the system will be printed based on this timezone. This setting has a lower priority than the user's timezone, if enabled and selected by the user himself through his extended profile.",
            ],
            [
                'name' => 'allow_user_course_subscription_by_course_admin',
                'title' => 'Allow User Course Subscription By Course Admininistrator',
                'comment' => 'Activate this option will allow course administrator to subscribe users inside a course',
            ],
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
                'name' => 'course_validation',
                'title' => 'Courses validation',
                'comment' => 'When the "Courses validation" feature is enabled, a teacher is not able to create a course alone. He/she fills a course request. The platform administrator reviews the request and approves it or rejects it.<br />This feature relies on automated e-mail messaging; set Chamilo to access an e-mail server and to use a dedicated an e-mail account.',
            ],
            [
                'name' => 'course_validation_terms_and_conditions_url',
                'title' => 'Course validation - a link to the terms and conditions',
                'comment' => "This is the URL to the \"Terms and Conditions\" document that is valid for making a course request. If the address is set here, the user should read and agree with these terms and conditions before sending a course request.<br />If you enable Chamilo's \"Terms and Conditions\" module and if you want its URL to be used, then leave this setting empty.",
            ],
            [
                'name' => 'sso_authentication',
                'title' => 'Single Sign On',
                'comment' => 'Enabling Single Sign On allows you to connect this platform as a slave of an authentication master, for example a Drupal website with the Drupal-Chamilo plugin or any other similar master setup.',
            ],
            [
                'name' => 'sso_authentication_domain',
                'title' => 'Domain of the Single Sign On server',
                'comment' => 'The domain of the Single Sign On server (the web address of the other server that will allow automatic registration to Chamilo). This should generally be the address of the other server without any trailing slash and without the protocol, e.g. www.example.com',
            ],
            [
                'name' => 'sso_authentication_auth_uri',
                'title' => 'Single Sign On server authentication URL',
                'comment' => "The address of the page that deals with the authentication verification. For example /?q=user in Drupal's case.",
            ],
            [
                'name' => 'sso_authentication_unauth_uri',
                'title' => "Single Sign On server's logout URL",
                'comment' => 'The address of the page on the server that logs the user out. This option is useful if you want users logging out of Chamilo to be automatically logged out of the authentication server.',
            ],
            [
                'name' => 'sso_authentication_protocol',
                'title' => "Single Sign On server's protocol",
                'comment' => "The protocol string to prefix the Single Sign On server's domain (we recommend you use https:// if your server is able to provide this feature, as all non-secure protocols are dangerous for authentication matters)",
            ],
            [
                'name' => 'enabled_wiris',
                'title' => 'WIRIS mathematical editor',
                'comment' => "Enable WIRIS mathematical editor. Installing this plugin you get WIRIS editor and WIRIS CAS.<br/>This activation is not fully realized unless it has been previously downloaded the <a href=\"http://www.wiris.com/es/plugins3/ckeditor/download\" target=\"_blank\">PHP plugin for CKeditor WIRIS</a> and unzipped its contents in the Chamilo's directory main/inc/lib/javascript/ckeditor/plugins/.<br/>This is necessary because Wiris is proprietary software and his services are <a href=\"http://www.wiris.com/store/who-pays\" target=\"_blank\">commercial</a>. To make adjustments to the plugin, edit configuration.ini file or replace his content by the file configuration.ini.default shipped with Chamilo.",
            ],
            [
                'name' => 'allow_spellcheck',
                'title' => 'Spell check',
                'comment' => 'Enable spell check',
            ],
            [
                'name' => 'force_wiki_paste_as_plain_text',
                'title' => 'Forcing pasting as plain text in the wiki',
                'comment' => 'This will prevent many hidden tags, incorrect or non-standard, copied from other texts to stop corrupting the text of the Wiki after many issues; but will lose some features while editing.',
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
                'name' => 'enabled_support_svg',
                'title' => 'Create and edit SVG files',
                'comment' => 'This option allows you to create and edit SVG (Scalable Vector Graphics) multilayer online, as well as export them to png format images.',
            ],
            [
                'name' => 'pdf_export_watermark_enable',
                'title' => 'Enable watermark in PDF export',
                'comment' => 'By enabling this option, you can upload an image or a text that will be automatically added as watermark to all PDF exports of documents on the system.',
            ],
            [
                'name' => 'pdf_export_watermark_by_course',
                'title' => 'Enable watermark definition by course',
                'comment' => 'When this option is enabled, teachers can define their own watermark for the documents in their courses.',
            ],
            [
                'name' => 'pdf_export_watermark_text',
                'title' => 'PDF watermark text',
                'comment' => 'This text will be added as a watermark to the documents exports as PDF.',
            ],
            [
                'name' => 'enabled_insertHtml',
                'title' => 'Allow insertion of widgets',
                'comment' => 'This allows you to embed on your webpages your favorite videos and applications such as vimeo or slideshare and all sorts of widgets and gadgets',
            ],
            [
                'name' => 'students_export2pdf',
                'title' => 'Allow learners to export web documents to PDF format in the documents and wiki tools',
                'comment' => 'This feature is enabled by default, but in case of server overload abuse it, or specific learning environments, might want to disable it for all courses.',
            ],
            [
                'name' => 'exercise_min_score',
                'title' => 'Minimum score of exercises',
                'comment' => 'Define a minimum score (generally 0) for all the exercises on the platform. This will define how final results are shown to users and teachers.',
            ],
            [
                'name' => 'exercise_max_score',
                'title' => 'Maximum score of exercises',
                'comment' => 'Define a maximum score (generally 10,20 or 100) for all the exercises on the platform. This will define how final results are shown to users and teachers.',
            ],
            [
                'name' => 'show_users_folders',
                'title' => 'Show users folders in the documents tool',
                'comment' => 'This option allows you to show or hide to teachers the folders that the system generates for each user who visits the tool documents or send a file through the web editor. If you display these folders to the teachers, they may make visible or not the learners and allow each learner to have a specific place on the course where not only store documents, but where they can also create and edit web pages and to export to pdf, make drawings, make personal web templates, send files, as well as create, move and delete directories and files and make security copies from their folders. Each user of course have a complete document manager. Also, remember that any user can copy a file that is visible from any folder in the documents tool (whether or not the owner) to his/her portfolios or personal documents area of social network, which will be available for his/her can use it in other courses.',
            ],
            [
                'name' => 'show_default_folders',
                'title' => 'Show in documents tool all folders containing multimedia resources supplied by default',
                'comment' => 'Multimedia file folders containing files supplied by default organized in categories of video, audio, image and flash animations to use in their courses. Although you make it invisible into the document tool, you can still use these resources in the platform web editor.',
            ],
            [
                'name' => 'show_chat_folder',
                'title' => 'Show the history folder of chat conversations',
                'comment' => 'This will show to theacher the folder that contains all sessions that have been made in the chat, the teacher can make them visible or not learners and use them as a resource',
            ],
            [
                'name' => 'enabled_text2audio',
                'title' => 'Enable online services for text to speech conversion',
                'comment' => 'Online tool to convert text to speech. Uses speech synthesis technology to generate audio files saved into your course.',
            ],
            [
                'name' => 'course_hide_tools',
                'title' => 'Hide tools from teachers',
                'comment' => 'Check the tools you want to hide from teachers. This will prohibit access to the tool.',
            ],
            [
                'name' => 'enabled_support_pixlr',
                'title' => 'Enable external Pixlr services',
                'comment' => 'Pixlr allow you to edit, adjust and filter your photos with features similar to Photoshop. It is the ideal complement to process images based on bitmaps',
            ],
            [
                'name' => 'show_groups_to_users',
                'title' => 'Show classes to users',
                'comment' => 'Show the classes to users. Classes are a feature that allow you to register/unregister groups of users into a session or a course directly, reducing the administrative hassle. When you pick this option, learners will be able to see in which class they are through their social network interface.',
            ],
            [
                'name' => 'accessibility_font_resize',
                'title' => 'Font resize accessibility feature',
                'comment' => 'Enable this option to show a set of font resize options on the top-right side of your campus. This will allow visually impaired to read their course contents more easily.',
            ],
            [
                'name' => 'hide_courses_in_sessions',
                'title' => 'Hide courses list in sessions',
                'comment' => 'When showing the session block in your courses page, hide the list of courses inside that session (only show them inside the specific session screen).',
            ],
            [
                'name' => 'enable_quiz_scenario',
                'title' => 'Enable Quiz scenario',
                'comment' => "From here you will be able to create exercises that propose different questions depending in the user's answers.",
            ],
            [
                'name' => 'filter_terms',
                'title' => 'Filter terms',
                'comment' => 'Give a list of terms, one by line, to be filtered out of web pages and e-mails. These terms will be replaced by ***.',
            ],
            [
                'name' => 'header_extra_content',
                'title' => 'Extra content in header',
                'comment' => 'You can add HTML code like meta tags',
            ],
            [
                'name' => 'footer_extra_content',
                'title' => 'Extra content in footer',
                'comment' => 'You can add HTML code like meta tags',
            ],
            [
                'name' => 'show_documents_preview',
                'title' => 'Show document preview',
                'comment' => 'Showing previews of the documents in the documents tool will avoid loading a new page just to show a document, but can result unstable with some older browsers or smaller width screens.',
            ],
            [
                'name' => 'htmlpurifier_wiki',
                'title' => 'HTMLPurifier in Wiki',
                'comment' => 'Enable HTML purifier in the wiki tool (will increase security but reduce style features)',
            ],
            [
                'name' => 'cas_activate',
                'title' => 'Enable CAS authentication',
                'comment' => "Enabling CAS authentication will allow users to authenticate with their CAS credentials.<br/>Go to <a href='settings.php?category=CAS'>Plugin</a> to add a configurable 'CAS Login' button for your Chamilo campus. Or you can force CAS authentication by setting cas[force_redirect] in app/config/auth.conf.php.",
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
                'name' => 'cas_add_user_activate',
                'title' => 'Enable CAS user addition',
                'comment' => 'Enable CAS user addition. To create the user account from the LDAP directory, the extldap_config and extldap_user_correspondance tables must be filled in in app/config/auth.conf.php',
            ],
            [
                'name' => 'update_user_info_cas_with_ldap',
                'title' => 'Update CAS-authenticated user account information from LDAP',
                'comment' => 'Makes sure the user firstname, lastname and email address are the same as current values in the LDAP directory',
            ],
            [
                'name' => 'student_page_after_login',
                'title' => 'Learner page after login',
                'comment' => 'This page will appear to all learners after they login',
            ],
            [
                'name' => 'teacher_page_after_login',
                'title' => 'Teacher page after login',
                'comment' => 'This page will be loaded after login for all teachers',
            ],
            [
                'name' => 'drh_page_after_login',
                'title' => 'Human resources manager page after login',
                'comment' => 'This page will load after login for all human resources managers',
            ],
            [
                'name' => 'sessionadmin_page_after_login',
                'title' => 'Session admin page after login',
                'comment' => 'Page to load after login for the session administrators',
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
            [
                'name' => 'drh_autosubscribe',
                'title' => 'Human resources director autosubscribe',
                'comment' => 'Human resources director autosubscribe - not yet available',
            ],
            [
                'name' => 'sessionadmin_autosubscribe',
                'title' => 'Session admin autosubscribe',
                'comment' => 'Session administrator autosubscribe - not available yet',
            ],
            [
                'name' => 'scorm_cumulative_session_time',
                'title' => 'Cumulative session time for SCORM',
                'comment' => 'When enabled, the session time for SCORM Learning Paths will be cumulative, otherwise, it will only be counted from the last update time. This is a global setting. It is used when creating a new Learning Path but can then be redefined for each one.',
            ],
            [
                'name' => 'allow_hr_skills_management',
                'title' => 'Allow HR skills management',
                'comment' => 'Allows HR to manage skills',
            ],
            [
                'name' => 'enable_help_link',
                'title' => 'Enable help link',
                'comment' => 'The Help link is located in the top right part of the screen',
            ],
            [
                'name' => 'teachers_can_change_score_settings',
                'title' => 'Teachers can change the Gradebook score settings',
                'comment' => 'When editing the Gradebook settings',
            ],
            [
                'name' => 'allow_users_to_change_email_with_no_password',
                'title' => 'Allow users to change e-mail without password',
                'comment' => 'When changing the account information',
            ],
            [
                'name' => 'show_admin_toolbar',
                'title' => 'Show admin toolbar',
                'comment' => "Shows a global toolbar on top of the page to the designated user roles. This toolbar, very similar to Wordpress and Google's black toolbars, can really speed up complicated actions and improve the space you have available for the learning content, but it might be confusing for some users",
            ],
            [
                'name' => 'allow_global_chat',
                'title' => 'Allow global chat',
                'comment' => 'Users can chat with each other',
            ],
            [
                'name' => 'languagePriority1',
                'title' => 'Language priority 1',
                'comment' => 'The language with the highest priority',
            ],
            [
                'name' => 'languagePriority2',
                'title' => 'Language priority 2',
                'comment' => 'The second language priority',
            ],
            [
                'name' => 'languagePriority3',
                'title' => 'Language priority 3',
                'comment' => 'The third language priority',
            ],
            [
                'name' => 'languagePriority4',
                'title' => 'Language priority 4',
                'comment' => 'The fourth language priority',
            ],
            [
                'name' => 'login_is_email',
                'title' => 'Use the email as username',
                'comment' => 'Use the email in order to login to the system',
            ],
            [
                'name' => 'courses_default_creation_visibility',
                'title' => 'Default course visibility',
                'comment' => 'Default course visibility while creating a new course',
            ],
            [
                'name' => 'gradebook_enable_grade_model',
                'title' => 'Enable Gradebook model',
                'comment' => 'Enables the auto creation of gradebook categories inside a course depending of the gradebook models.',
            ],
            [
                'name' => 'teachers_can_change_grade_model_settings',
                'title' => 'Teachers can change the Gradebook model settings',
                'comment' => 'When editing a Gradebook',
            ],
            [
                'name' => 'gradebook_default_weight',
                'title' => 'Default weight in Gradebook',
                'comment' => 'This weight will be use in all courses by default',
            ],
            [
                'name' => 'ldap_description',
                'title' => '<h3>LDAP autentication</h3>',
                'comment' => 'To update correspondences between user and LDAP attributes, edit array',
            ],
            [
                'name' => 'shibboleth_description',
                'title' => '<h3>Shibboleth authentication</h3>',
                'comment' => "<p>First of all, you have to configure Shibboleth for your web server.</p>To configure it for Chamilo<h5>edit file main/auth/shibboleth/config/aai.class.php</h5><p>Modify object &#36;result values with the name of your Shibboleth attributes</p><ul><li>&#36;result-&gt;unique_id = 'mail';</li><li>&#36;result-&gt;firstname = 'cn';</li><li>&#36;result-&gt;lastname = 'uid';</li><li>&#36;result-&gt;email = 'mail';</li><li>&#36;result-&gt;language = '-';</li><li>&#36;result-&gt;gender = '-';</li><li>&#36;result-&gt;address = '-';</li><li>&#36;result-&gt;staff_category = '-';</li><li>&#36;result-&gt;home_organization_type = '-'; </li><li>&#36;result-&gt;home_organization = '-';</li><li>&#36;result-&gt;affiliation = '-';</li><li>&#36;result-&gt;persistent_id = '-';</li><li>...</li></ul><br/>Go to <a href='settings.php?category=Shibboleth'>Plugin</a> to add a configurable 'Shibboleth Login' button for your Chamilo campus.",
            ],
            [
                'name' => 'facebook_description',
                'title' => 'Facebook authentication',
                'comment' => "<p><h5>Create your Facebook Application</h5>First of all, you have to create a Facebook Application (see <a href='https://developers.facebook.com/apps'>https://developers.facebook.com/apps</a>) with your Facebook account. In the Facebook Apps settings, the site URL value should be the URL of this campus. Enable the Web OAuth Login option. And add the site URL of your campus to the Valid OAuth redirect URIs field</p><p>Uncomment the line <code>&#36;_configuration['facebook_auth'] = 1;</code> to enable the Facebook Auth.</p><p>Then, edit the <code>app/config/auth.conf.php</code> file and enter '<code>appId</code>' and '<code>secret</code>' values for <code>&#36;facebook_config</code>.</p><p>Go to <a href='settings.php?category=Plugins'>Plugins</a> to add a configurable <em>Facebook Login</em> button for your Chamilo campus.</p>",
            ],
            [
                'name' => 'gradebook_locking_enabled',
                'title' => 'Enable locking of assessments by teachers',
                'comment' => "Once enabled, this option will enable locking of any assessment by the teachers of the corresponding course. This, in turn, will prevent any modification of results by the teacher inside the resources used in the assessment: exams, learning paths, tasks, etc. The only role authorized to unlock a locked assessment is the administrator. The teacher will be informed of this possibility. The locking and unlocking of gradebooks will be registered in the system's report of important activities",
            ],
            [
                'name' => 'gradebook_default_grade_model_id',
                'title' => 'Default grade model',
                'comment' => 'This value will be selected by default when creating a course',
            ],
            [
                'name' => 'allow_session_admins_to_manage_all_sessions',
                'title' => 'Allow session administrators to see all sessions',
                'comment' => 'When this option is not enabled (default), session administrators can only see the sessions they have created. This is confusing in an open environment where session administrators might need to share support time between two sessions.',
            ],
            [
                'name' => 'allow_skills_tool',
                'title' => 'Allow Skills tool',
                'comment' => 'Users can see their skills in the social network and in a block in the homepage.',
            ],
            [
                'name' => 'allow_public_certificates',
                'title' => 'Allow public certificates',
                'comment' => 'User certificates can be view by unregistered users.',
            ],
            [
                'name' => 'platform_unsubscribe_allowed',
                'title' => 'Allow unsubscription from platform',
                'comment' => 'By enabling this option, you allow any user to definitively remove his own account and all data related to it from the platform. This is quite a radical action, but it is necessary for portals opened to the public where users can auto-register. An additional entry will appear in the user profile to unsubscribe after confirmation.',
            ],
            [
                'name' => 'activate_email_template',
                'title' => 'Enable e-mail alerts templates',
                'comment' => 'Define home-made e-mail alerts to be fired on specific events (and to specific users)',
            ],
            [
                'name' => 'enable_iframe_inclusion',
                'title' => 'Allow iframes in HTML Editor',
                'comment' => 'Allowing arbitrary iframes in the HTML Editor will enhance the edition capabilities of the users, but it can represent a security risk. Please make sure you can rely on your users (i.e. you know who they are) before enabling this feature.',
            ],
            [
                'name' => 'show_hot_courses',
                'title' => 'Show hot courses',
                'comment' => 'The hot courses list will be added in the index page',
            ],
            [
                'name' => 'enable_webcam_clip',
                'title' => 'Enable Webcam Clip',
                'comment' => 'Webcam Clip allow to users capture images from his webcam and send them to server in JPEG (.jpg or .jpeg) format',
            ],
            [
                'name' => 'use_custom_pages',
                'title' => 'Use custom pages',
                'comment' => 'Enable this feature to configure specific login pages by role',
            ],
            [
                'name' => 'tool_visible_by_default_at_creation',
                'title' => 'Tool visible at course creation',
                'comment' => 'Select the tools that will be visible when creating the courses - not yet available',
            ],
            [
                'name' => 'prevent_session_admins_to_manage_all_users',
                'title' => 'Prevent session admins to manage all users',
                'comment' => 'By enabling this option, session admins will only be able to see, in the administration page, the users they created.',
            ],
            [
                'name' => 'documents_default_visibility_defined_in_course',
                'title' => 'Document visibility defined in course',
                'comment' => 'The default document visibility for all courses',
            ],
            [
                'name' => 'enabled_mathjax',
                'title' => 'Enable MathJax',
                'comment' => 'Enable the MathJax library to visualize mathematical formulas. This is only useful if either ASCIIMathML or ASCIISVG settings are enabled.',
            ],
            [
                'name' => 'meta_twitter_site',
                'title' => 'Twitter Site account',
                'comment' => 'The Twitter site is a Twitter account (e.g. @chamilo_news) that is related to your site. It is usually a more temporary account than the Twitter creator account, or represents an entity (instead of a person). This field is required if you want the Twitter card meta fields to show.',
            ],
            [
                'name' => 'meta_twitter_creator',
                'title' => 'Twitter Creator account',
                'comment' => 'The Twitter Creator is a Twitter account (e.g. @ywarnier) that represents the *person* that created the site. This field is optional.',
            ],
            [
                'name' => 'meta_title',
                'title' => 'OpenGraph meta title',
                'comment' => "This will show an OpenGraph Title meta (og:title) in your site's headers",
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
                'name' => 'allow_teachers_to_create_sessions',
                'title' => 'Allow teachers to create sessions',
                'comment' => 'Teachers can create, edit and delete their own sessions.',
            ],
            [
                'name' => 'institution_address',
                'title' => 'Institution address',
                'comment' => 'Address',
            ],
            [
                'name' => 'cron_remind_course_finished_activate',
                'title' => 'Send course finished notification',
                'comment' => 'Whether to send an e-mail to students when their course (session) is finished. This requires cron tasks to be configured (see main/cron/ directory).',
            ],
            [
                'name' => 'cron_remind_course_expiration_frequency',
                'title' => 'Frequency for the Remind Course Expiration cron',
                'comment' => 'Number of days before the expiration of the course to consider to send reminder mail',
            ],
            [
                'name' => 'cron_remind_course_expiration_activate',
                'title' => 'Remind Course Expiration cron',
                'comment' => 'Enable the Remind Course Expiration cron',
            ],
            [
                'name' => 'allow_coach_feedback_exercises',
                'title' => 'Allow coaches to comment in review of exercises',
                'comment' => 'Allow coaches to edit feedback during review of exercises',
            ],
            [
                'name' => 'allow_my_files',
                'title' => "Enable 'My Files' section",
                'comment' => 'Allow users to upload files to a personal space on the platform.',
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
            [
                'name' => 'ticket_allow_category_edition',
                'title' => 'Allow tickets categories edition',
                'comment' => 'Allow category edition by administrators.',
            ],
            [
                'name' => 'load_term_conditions_section',
                'title' => 'Load term conditions section',
                'comment' => 'The legal agreement will appear during the login or when enter to a course.',
            ],
            [
                'name' => 'show_terms_if_profile_completed',
                'title' => 'Terms and conditions only if profile complete',
                'comment' => "By enabling this option, terms and conditions will be available to the user only when the extra profile fields that start with 'terms_' and set to visible are completed.",
            ],
            [
                'name' => 'hide_home_top_when_connected',
                'title' => 'Hide top content on homepage when logged in',
                'comment' => 'On the platform homepage, this option allows you to hide the introduction block (to leave only the announcements, for example), for all users that are already logged in. The general introduction block will still appear to users not already logged in.',
            ],
            [
                'name' => 'hide_global_announcements_when_not_connected',
                'title' => 'Hide global announcements for anonymous',
                'comment' => 'Hide platform announcements from anonymous users, and only show them to authenticated users.',
            ],
            [
                'name' => 'course_creation_use_template',
                'title' => 'Use template course for new courses',
                'comment' => 'Set this to use the same template course (identified by its course numeric ID in the database) for all new courses that will be created on the platform. Please note that, if not properly planned, this setting might have a massive impact on space usage. The template course will be used as if the teacher did a copy of the course with the course backup tools, so no user content is copied, only teacher material. All other course-backup rules apply. Leave empty (or set to 0) to disable.',
            ],
            [
                'name' => 'allow_strength_pass_checker',
                'title' => 'Password strength checker',
                'comment' => 'Enable this option to add a visual indicator of password strength, when the user changes his/her password. This will NOT prevent bad passwords to be added, it only acts as a visual helper.',
            ],
            [
                'name' => 'allow_captcha',
                'title' => 'CAPTCHA',
                'comment' => 'Enable a CAPTCHA on the login form, inscription form and lost password form to avoid password hammering',
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
                'name' => 'drh_can_access_all_session_content',
                'title' => 'HR directors access all session content',
                'comment' => 'If enabled, human resources directors will get access to all content and users from the sessions (s)he follows.',
            ],
            [
                'name' => 'display_groups_forum_in_general_tool',
                'title' => 'Display group forums in general forum',
                'comment' => 'Display group forums in the forum tool at the course level. This option is enabled by default (in this case, group forum individual visibilities still act as an additional criteria). If disabled, group forums will only be visible through the group tool, be them public or not.',
            ],
            [
                'name' => 'allow_tutors_to_assign_students_to_session',
                'title' => 'Tutors can assign students to sessions',
                'comment' => 'When enabled, course coaches/tutors in sessions can subscribe new users to their session. This option is otherwise only available to administrators and session administrators.',
            ],
            [
                'name' => 'allow_lp_return_link',
                'title' => 'Show learning paths return link',
                'comment' => "Disable this option to hide the 'Return to homepage' button in the learning paths",
            ],
            [
                'name' => 'hide_scorm_export_link',
                'title' => 'Hide SCORM Export',
                'comment' => 'Hide the SCORM Export icon from the Learning Paths list',
            ],
            [
                'name' => 'hide_scorm_copy_link',
                'title' => 'Hide SCORM Copy',
                'comment' => 'Hide the Learning Path Copy icon from the Learning Paths list',
            ],
            [
                'name' => 'hide_scorm_pdf_link',
                'title' => 'Hide Learning Path PDF export',
                'comment' => 'Hide the Learning Path PDF Export icon from the Learning Paths list',
            ],
            [
                'name' => 'session_days_before_coach_access',
                'title' => 'Default coach access days before session',
                'comment' => 'Default number of days a coach can access his session before the official session start date',
            ],
            [
                'name' => 'session_days_after_coach_access',
                'title' => 'Default coach access days after session',
                'comment' => 'Default number of days a coach can access his session after the official session end date',
            ],
            [
                'name' => 'pdf_logo_header',
                'title' => 'PDF header logo',
                'comment' => 'Whether to use the image at css/themes/[your-css]/images/pdf_logo_header.png as the PDF header logo for all PDF exports (instead of the normal portal logo)',
            ],
            [
                'name' => 'order_user_list_by_official_code',
                'title' => 'Order users by official code',
                'comment' => "Use the 'official code' to sort most students list on the platform, instead of their lastname or firstname.",
            ],
            [
                'name' => 'email_alert_manager_on_new_quiz',
                'title' => 'Default e-mail alert setting on new quiz',
                'comment' => 'Whether you want course managers (teachers) to be notified by e-mail when a quiz is answered by a student. This is the default value to be given to all new courses, but each teacher can still change this setting in his/her own course.',
            ],
            [
                'name' => 'show_official_code_exercise_result_list',
                'title' => 'Display official code in exercises results',
                'comment' => "Whether to show the students' official code in the exercises results reports",
            ],
            [
                'name' => 'course_catalog_hide_private',
                'title' => 'Hide private courses from catalogue',
                'comment' => 'Whether to hide the private courses from the courses catalogue. This makes sense when you use the course catalogue mainly to allow students to auto-subscribe to the courses.',
            ],
            [
                'name' => 'catalog_show_courses_sessions',
                'title' => 'Sessions and courses catalogue',
                'comment' => 'Whether you want to show only courses, only sessions or both courses *and* sessions in the courses catalogue.',
            ],
            [
                'name' => 'auto_detect_language_custom_pages',
                'title' => 'Enable language auto-detect in custom pages',
                'comment' => "If you use custom pages, enable this if you want to have a language detector there present the page in the user's browser language, or disable to force the language to be the default platform language.",
            ],
            [
                'name' => 'lp_show_reduced_report',
                'title' => 'Learning paths: show reduced report',
                'comment' => 'Inside the learning paths tool, when a user reviews his own progress (through the stats icon), show a shorten (less detailed) version of the progress report.',
            ],
            [
                'name' => 'allow_session_course_copy_for_teachers',
                'title' => 'Allow session-to-session copy for teachers',
                'comment' => 'Enable this option to let teachers copy their content from one course in a session to a course in another session. By default, this option is only available to platform administrators.',
            ],
            [
                'name' => 'hide_logout_button',
                'title' => 'Hide logout button',
                'comment' => 'Hide the logout button. This is usually only interesting when using an external login/logout method, for example when using Single Sign On of some sort.',
            ],
            [
                'name' => 'redirect_admin_to_courses_list',
                'title' => 'Redirect admin to courses list',
                'comment' => 'The default behaviour is to send administrators directly to the administration panel (while teachers and students are sent to the courses list or the platform homepage). Enable to redirect the administrator also to his/her courses list.',
            ],
            [
                'name' => 'course_images_in_courses_list',
                'title' => 'Courses custom icons',
                'comment' => 'Use course images as the course icon in courses lists (instead of the default green blackboard icon).',
            ],
            [
                'name' => 'student_publication_to_take_in_gradebook',
                'title' => 'Assignment considered for gradebook',
                'comment' => "In the assignments tool, students can upload more than one file. In case there is more than one for a single assignment, which one should be considered when ranking them in the gradebook? This depends on your methodology. Use 'first' to put the accent on attention to detail (like handling in time and handling the right work first). Use 'last' to highlight collaborative and adaptative work.",
            ],
            [
                'name' => 'certificate_filter_by_official_code',
                'title' => 'Certificates filter by official code',
                'comment' => 'Add a filter on the students official code to the certificates list.',
            ],
            [
                'name' => 'exercise_max_ckeditors_in_page',
                'title' => 'Max editors in exercise result screen',
                'comment' => 'Because of the sheer number of questions that might appear in an exercise, the correction screen, allowing the teacher to add comments to each answer, might be very slow to load. Set this number to 5 to ask the platform to only show WYSIWYG editors up to a certain number of answers on the screen. This will speed up the correction page loading time considerably, but will remove WYSIWYG editors and leave only a plain text editor.',
            ],
            [
                'name' => 'document_if_file_exists_option',
                'title' => 'Default document upload mode',
                'comment' => 'Default upload method in the courses documents. This setting can be changed at upload time for all users. It only represents a default setting.',
            ],
            [
                'name' => 'add_gradebook_certificates_cron_task_enabled',
                'title' => 'Certificates auto-generation on WS call',
                'comment' => 'When enabled, and when using the WSCertificatesList webservice, this option will make sure that all certificates have been generated by users if they reached the sufficient score in all items defined in gradebooks for all courses and sessions (this might consume considerable processing resources on your server).',
            ],
            [
                'name' => 'openbadges_backpack',
                'title' => 'OpenBadges backpack URL',
                'comment' => 'The URL of the OpenBadges backpack server that will be used by default for all users wanting to export their badges. This defaults to the open and free Mozilla Foundation backpack repository: https://backpack.openbadges.org/',
            ],
            [
                'name' => 'cookie_warning',
                'title' => 'Cookie privacy notification',
                'comment' => 'If enabled, this option shows a banner on top of your platform that asks users to acknowledge that the platform is using cookies necessary to provide the user experience. The banner can easily be acknowledged and hidden by the user. This allows Chamilo to comply with EU web cookies regulations.',
            ],
            [
                'name' => 'hide_course_group_if_no_tools_available',
                'title' => 'Hide course group if no tool',
                'comment' => 'If no tool is available in a group and the user is not registered to the group itself, hide the group completely in the groups list.',
            ],
            [
                'name' => 'catalog_allow_session_auto_subscription',
                'title' => 'Auto-subscription in sessions catalogue',
                'comment' => 'Auto-subscription in sessions catalogue',
            ],
            [
                'name' => 'registration.soap.php.decode_utf8',
                'title' => 'Web services: decode UTF-8',
                'comment' => 'Decode UTF-8 from web services calls. Enable this option (passed to the SOAP parser) if you have issues with the encoding of titles and names when inserted through web services.',
            ],
            [
                'name' => 'allow_delete_attendance',
                'title' => 'Attendances: enable deletion',
                'comment' => 'The default behaviour in Chamilo is to hide attendance sheets instead of deleting them, just in case the teacher would do it by mistake. Enable this option to allow teachers to *really* delete attendance sheets.',
            ],
            [
                'name' => 'gravatar_enabled',
                'title' => 'Gravatar user pictures',
                'comment' => "Enable this option to search into the Gravatar repository for pictures of the current user, if the user hasn't defined a picture locally. This is great to auto-fill pictures on your site, in particular if your users are active internet users. Gravatar pictures can be configured easily, based on the e-mail address of a user, at http://en.gravatar.com/",
            ],
            [
                'name' => 'gravatar_type',
                'title' => 'Gravatar avatar type',
                'comment' => "If the Gravatar option is enabled and the user doesn't have a picture configured on Gravatar, this option allows you to choose the type of avatar that Gravatar will generate for each user. Check <a href='http://en.gravatar.com/site/implement/images#default-image'>http://en.gravatar.com/site/implement/images#default-image</a> for avatar types examples.",
            ],
            [
                'name' => 'limit_session_admin_role',
                'title' => 'Limit session admins permissions',
                'comment' => "If enabled, the session administrators will only see the User block with the 'Add user' option and the Sessions block with the 'Sessions list' option.",
            ],
            [
                'name' => 'show_session_description',
                'title' => 'Show session description',
                'comment' => 'Show the session description wherever this option is implemented (sessions tracking pages, etc)',
            ],
            [
                'name' => 'hide_certificate_export_link_students',
                'title' => 'Certificates: hide export link from students',
                'comment' => "If enabled, students won't be able to export their certificates to PDF. This option is available because, depending on the precise HTML structure of the certificate template, the PDF export might be of low quality. In this case, it is best to only show the HTML certificate to students.",
            ],
            [
                'name' => 'hide_certificate_export_link',
                'title' => 'Certificates: hide PDF export link for all',
                'comment' => 'Enable to completely remove the possibility to export certificates to PDF (for all users). If enabled, this includes hiding it from students.',
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
            [
                'name' => 'sso_force_redirect',
                'title' => 'Single Sign On: force redirect',
                'comment' => 'Enable this option to force users to authenticate on the master authentication portal when a Single Sign On method is enabled. Only enable once you are sure that your Single Sign On procedure is correctly set up, otherwise you might prevent yourself from logging in again (in this case, change the SSO settings in the settings_current table through direct access to the database, to unlock).',
            ],
            [
                'name' => 'session_course_ordering',
                'title' => 'Session courses manual ordering',
                'comment' => 'Enable this option to allow the session administrators to order the courses inside a session manually. If disabled, courses are ordered alphabetically on course title.',
            ],
            [
                'name' => 'gamification_mode',
                'title' => 'Gamification mode',
                'comment' => 'Activate the stars achievement in learning paths',
            ],
            [
                'name' => 'prevent_multiple_simultaneous_login',
                'title' => 'Prevent simultaneous login',
                'comment' => 'Prevent users connecting with the same account more than once. This is a good option on pay-per-access portals, but might be restrictive during testing as only one browser can connect with any given account.',
            ],
            [
                'name' => 'gradebook_detailed_admin_view',
                'title' => 'Show additional columns in gradebook',
                'comment' => 'Show additional columns in the student view of the gradebook with the best score of all students, the relative position of the student looking at the report and the average score of the whole group of students.',
            ],
            [
                'name' => 'course_catalog_published',
                'title' => 'Publish course catalogue',
                'comment' => 'Make the courses catalogue available to anonymous users (the general public) without the need to login.',
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
                'name' => 'my_courses_view_by_session',
                'title' => 'View my courses by session',
                'comment' => "Enable an additional 'My courses' page where sessions appear as part of courses, rather than the opposite.",
            ],
            [
                'name' => 'show_full_skill_name_on_skill_wheel',
                'title' => 'Show full skill name on skill wheel',
                'comment' => 'On the wheel of skills, it shows the name of the skill when it has short code.',
            ],
            [
                'name' => 'messaging_allow_send_push_notification',
                'title' => 'Allow Push Notifications to the Chamilo Messaging mobile app',
                'comment' => "Send Push Notifications by Google's Firebase Console",
            ],
            [
                'name' => 'messaging_gdc_project_number',
                'title' => 'Sender ID of Firebase Console for Cloud Messaging',
                'comment' => 'You need register a project on <a href="https://console.firebase.google.com/">Google Firebase Console</a>',
            ],
            [
                'name' => 'messaging_gdc_api_key',
                'title' => 'Server key of Firebase Console for Cloud Messaging',
                'comment' => 'Server key (legacy token) from project credentials',
            ],
            [
                'name' => 'teacher_can_select_course_template',
                'title' => 'Teacher can select a course as template',
                'comment' => 'Allow pick a course as template for the new course that teacher is creating',
            ],
            [
                'name' => 'enable_record_audio',
                'title' => 'Enable audio recorder',
                'comment' => 'Enables the WebRTC (flashless) audio recorder at several locations inside Chamilo',
            ],
            [
                'name' => 'allow_show_skype_account',
                'title' => 'Allow show the user Skype account',
                'comment' => 'Add a link on the user social block allowing start a chat by Skype',
            ],
            [
                'name' => 'allow_show_linkedin_url',
                'title' => 'Allow show the user LinkedIn URL',
                'comment' => "Add a link on the user social block, allowing visit the user's LinkedIn profile",
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
            [
                'name' => 'icons_mode_svg',
                'title' => 'SVG icons mode',
                'comment' => 'By enabling this option, all icons that have an SVG version will prefer the SVG format to PNG. This will give a much better icons quality but some icons might still have some rendering size issue, and some browsers might not support it.',
            ],
            [
                'name' => 'allow_download_documents_by_api_key',
                'title' => 'Allow download course documents by API Key',
                'comment' => 'Download documents verifying the REST API key for a user',
            ],
            [
                'name' => 'exercise_invisible_in_session',
                'title' => 'Exercise invisible in Session',
                'comment' => 'If an exercise is visible in the base course then it appears invisible in the session. If an exercise is invisible in the base course then it does not appear in the session.',
            ],
            [
                'name' => 'configure_exercise_visibility_in_course',
                'title' => 'Enable to bypass the configuration of Exercise invisible in session at a base course level',
                'comment' => 'To enable the configuration of the exercise invisibility in session in the base course to by pass the global configuration. If not set the global parameter is used.',
            ],
            [
                'name' => 'service_ppt2lp.host',
                'title' => 'Host',
                'comment' => 'Service host, if remote',
            ],
            [
                'name' => 'service_ppt2lp.port',
                'title' => 'Port',
                'comment' => 'Port',
            ],
            [
                'name' => 'service_ppt2lp.user',
                'title' => 'User on host',
                'comment' => 'Username on remote host',
            ],
            [
                'name' => 'service_ppt2lp.ftp_password',
                'title' => 'Ftp password',
                'comment' => 'Password on remote host',
            ],
        ];
    }

    public static function getNewConfigurationSettings(): array
    {
        return [
            [
                "name" => "show_link_request_hrm_user",
                "title" => "Show link to request bond between user and HRM",
                "comment" => ""
            ],
            [
                "name" => "max_anonymous_users",
                "title" => "Multiple anonymous users",
                "comment" => "Enable this option to allow multiple system users for anonymous users. This is useful when using this platform as a public showroom for some courses. Having multiple anonymous users will let tracking work for the duration of the experience for several users without mixing their data (which could otherwise confuse them)."
            ],
            [
                "name" => "send_inscription_notification_to_general_admin_only",
                "title" => "Notify global admin only of new users",
                "comment" => ""
            ],
            [
                "name" => "plugin_redirection_enabled",
                "title" => "Enable only if you are using the Redirection plugin",
                "comment" => ""
            ],
            [
                "name" => "usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe",
                "title" => "Disable user unsubscription from course/session on user unsubscription from group/class",
                "comment" => ""
            ],
            [
                "name" => "usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe",
                "title" => "Disable user unsubscription from course on course removal from group/class",
                "comment" => ""
            ],
            [
                "name" => "usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe",
                "title" => "Disable user unsubscription from session on session removal from group/class",
                "comment" => ""
            ],
            [
                "name" => "drh_allow_access_to_all_students",
                "title" => "HRM can access all students from reporting pages",
                "comment" => ""
            ],
            [
                "name" => "user_status_option_only_for_admin_enabled",
                "title" => "Hide role from normal users",
                "comment" => "Allows hiding users' role when this option is set to true and the following array sets the corresponding role to 'true'."
            ],
            [
                "name" => "user_status_option_show_only_for_admin",
                "title" => "Define which roles are hidden to normal users",
                "comment" => "The roles set to 'true' will only appear to administrators. Other users will not be able to see them."
            ],
            [
                "name" => "hide_send_to_hrm_users",
                "title" => "Hide the option to send an announcement copy to HRM",
                "comment" => "In the announcements form, an option normally appears to allow teachers to send a copy of the announcement to the user's HRM. Set this to \"Yes\" to remote the option (and *not* send the copy)."
            ],
            [
                "name" => "disable_announcement_attachment",
                "title" => "Disable attachment to announcements",
                "comment" => "Even though attachments in this version are dealt in an elegant way and do not multiply on disk, you might want to disable attachments altogether if you want to avoid excesses."
            ],
            [
                "name" => "admin_chamilo_announcements_disable",
                "title" => "Disable editor announcements",
                "comment" => "Choose 'Yes' to stop announcements from the software editor to appear on the administrative homepage (only admins see it). This will also remove security update announcements."
            ],
            [
                "name" => "allow_scheduled_announcements",
                "title" => "Enable scheduled announcements in sessions",
                "comment" => "Allows the sessions managers to set announcements that will be triggered on specific dates or after/before a number of days of start/end of the session. Enabling this feature requires you to setup a cron task."
            ],
            [
                "name" => "disable_delete_all_announcements",
                "title" => "Disable button to delete all announcements",
                "comment" => "Select 'Yes' to remove the button to delete all announcements, as this can be used by mistake by teachers."
            ],
            [
                "name" => "hide_announcement_sent_to_users_info",
                "title" => "Hide 'sent to' in announcements",
                "comment" => "Select 'Yes' to avoid showing to whom an announcement has been sent."
            ],
            [
                "name" => "send_all_emails_to",
                "title" => "Send all e-mails to",
                "comment" => "Give a list of e-mail addresses to whom *all* e-mails sent from the platform will be sent. The e-mails are sent to these addresses as a visible destination."
            ],
            [
                "name" => "allow_careers_in_global_announcements",
                "title" => "Link global announcements with careers and promotions",
                "comment" => ""
            ],
            [
                "name" => "allow_careers_in_global_agenda",
                "title" => "Link global calendar events with careers and promotions",
                "comment" => ""
            ],
            [
                "name" => "allow_coach_to_edit_announcements",
                "title" => "Allow coaches to always edit announcements",
                "comment" => "Allow coaches to always edit announcements inside active or past sessions."
            ],
            [
                "name" => "course_announcement_scheduled_by_date",
                "title" => "Date-based announcements",
                "comment" => "Allow teachers to configure announcements that will be sent at specific dates. This requires you to setup a cron task on cron/course_announcement.php running at least once daily."
            ],
            [
                "name" => "default_calendar_view",
                "title" => "Default calendar display mode",
                "comment" => "Set this to dayGridMonth, basicWeek, agendaWeek or agendaDay to change the default view of the calendar."
            ],
            [
                "name" => "personal_calendar_show_sessions_occupation",
                "title" => "Display sessions occupations in personal agenda",
                "comment" => ""
            ],
            [
                "name" => "personal_agenda_show_all_session_events",
                "title" => "Display all agenda events in personal agenda",
                "comment" => "Do not hide events from expired sessions."
            ],
            [
                "name" => "allow_agenda_edit_for_hrm",
                "title" => "Allow HRM role to edit or delete agenda events",
                "comment" => "This gives the HRM a little more power by allowing them to edit/delete agenda events in the course-session."
            ],
            [
                "name" => "agenda_legend",
                "title" => "Agenda colour legends",
                "comment" => "Add a small text as legend describing the colours used for the events."
            ],
            [
                "name" => "agenda_colors",
                "title" => "Agenda colours",
                "comment" => "Set HTML-code colours for each type of event to change the colour when displaying the event."
            ],
            [
                "name" => "agenda_on_hover_info",
                "title" => "Agenda hover info",
                "comment" => "Customize the agenda on cursor hovering. Show agenda comment and/or description."
            ],
            [
                "name" => "fullcalendar_settings",
                "title" => "Calendar customization",
                "comment" => "Extra settings for the agenda, allowing you to configure the specific calendar library we use."
            ],
            [
                "name" => "enable_sign_attendance_sheet",
                "title" => "Attendance signing",
                "comment" => "Enable taking signatures to confirm one's attendance."
            ],
            [
                "name" => "attendance_calendar_set_duration",
                "title" => "Duration of attendance events",
                "comment" => "Option to define the duration for an event in attendance sheet."
            ],
            [
                "name" => "attendance_allow_comments",
                "title" => "Allow comments in attendance sheets",
                "comment" => "Teachers and students can comment on each individual attendance (to justify)."
            ],
            [
                "name" => "hide_my_certificate_link",
                "title" => "Hide 'my certificate' link",
                "comment" => "Hide the certificates page for non-admin users."
            ],
            [
                "name" => "add_certificate_pdf_footer",
                "title" => "Add footer to PDF certificate exports",
                "comment" => ""
            ],
            [
                "name" => "hide_chat_video",
                "title" => "Hide videochat option in global chat",
                "comment" => ""
            ],
            [
                "name" => "course_chat_restrict_to_coach",
                "title" => "Restrict course chat to coaches",
                "comment" => "Only allow students to talk to the tutors in the course (not other students)."
            ],
            [
                "name" => "active_tools_on_create",
                "title" => "Active tools on course creation",
                "comment" => "Select the tools that will be *active* after the creation of a course."
            ],
            [
                "name" => "course_creation_splash_screen",
                "title" => "Splash screen for courses",
                "comment" => "Show a splash screen when creating a new course."
            ],
            [
                "name" => "block_registered_users_access_to_open_course_contents",
                "title" => "Block public courses access to authenticated users",
                "comment" => "Only show public courses. Do not allow registered users to access courses with 'open' visibility unless they are subscribed to each of these courses."
            ],
            [
                "name" => "enable_bootstrap_in_documents_html",
                "title" => "Add Bootstrap lib headers to HTML documents viewer",
                "comment" => "When this setting is set to 'Yes', the HTML documents viewer adds the HTML headers for the inclusion of Bootstrap and Font Awesome, which were used in Chamilo 1. This might help compatibility between old content and a newer version of the platform."
            ],
            [
                "name" => "view_grid_courses",
                "title" => "View courses in a grid layout",
                "comment" => "View courses in a layout with several courses per line. Otherwise, the layout will show one course per line."
            ],
            [
                "name" => "show_simple_session_info",
                "title" => "Show simple session info",
                "comment" => "Add coach and dates to the session's subtitle in the sessions' list."
            ],
            [
                "name" => "my_courses_show_courses_in_user_language_only",
                "title" => "Only show courses in the user's language",
                "comment" => "If enabled, this option will hide all courses not set in the user's language."
            ],
            [
                "name" => "allow_public_course_with_no_terms_conditions",
                "title" => "Access public courses with terms and conditions",
                "comment" => "With this option enabled, if a course has public visibility and terms and conditions, those terms are disabled while the course is public."
            ],
            [
                "name" => "show_all_sessions_on_my_course_page",
                "title" => "Show all sessions on 'My courses' page",
                "comment" => "If enabled, this option show all sessions of the user in calendar-based view."
            ],
            [
                "name" => "disabled_edit_session_coaches_course_editing_course",
                "title" => "Disable the ability to edit course coaches",
                "comment" => "When disabled, admins do not have a link to quickly assign coaches to session-courses on the course edition page."
            ],
            [
                "name" => "allow_base_course_category",
                "title" => "Use course categories from top URL",
                "comment" => "In multi-URL settings, allow admins and teachers to assign categories from the top URL to courses in the children URLs."
            ],
            [
                "name" => "hide_course_sidebar",
                "title" => "Hide courses block in the sidebar",
                "comment" => "When on screens where the left menu is visible, do not display the « Courses » section."
            ],
            [
                "name" => "allow_course_extra_field_in_catalog",
                "title" => "Allow using extra fields in course catalogue",
                "comment" => "Add new search fields dynamically to the course catalogue based on searchable course extra fields."
            ],
            [
                "name" => "multiple_access_url_show_shared_course_marker",
                "title" => "Show multi-URL shared course marker",
                "comment" => "Adds a link icon to courses that are shared between URLs, so users (in particular teachers) know they have to take special care when editing the course content."
            ],
            [
                "name" => "course_category_code_to_use_as_model",
                "title" => "Restrict course templates to one course category",
                "comment" => "Give a category code to use as course templates. Only those courses will show in the drop-down at course creation time, and users won’t see the courses in this category from the courses catalogue."
            ],
            [
                "name" => "enable_unsubscribe_button_on_my_course_page",
                "title" => "Show unsubscribe button in ‘My courses’",
                "comment" => "Add a button to unsubscribe from a course on the ‘My courses’ page."
            ],
            [
                "name" => "course_creation_donate_message_show",
                "title" => "Show donate message on course creation page",
                "comment" => "Add a message box in the course creation page for teachers, asking them to donate to the project."
            ],
            [
                "name" => "course_creation_donate_link",
                "title" => "Donation link on course creation page",
                "comment" => "The page the donation message should link to (full URL)."
            ],
            [
                "name" => "courses_list_session_title_link",
                "title" => "Type of link for the session title",
                "comment" => "On the courses/sessions page, the session title can be either of the following : 0 = no link (hide session title) ; 1 = link title to a special session page ; 2 = link to the course if there is only one course ; 3 = session title makes the courses list foldable ; 4 = no link (show session title)."
            ],
            [
                "name" => "hide_course_rating",
                "title" => "Hide course rating",
                "comment" => "The course rating feature comes by default in different places. If you don’t want it, enable this option."
            ],
            [
                "name" => "course_log_hide_columns",
                "title" => "Hide columns from course logs",
                "comment" => "This array gives you the possibility to configure which columns to hide in the main course stats page and in the total time report."
            ],
            [
                "name" => "course_student_info",
                "title" => "Course student info display",
                "comment" => "On the ‘My courses’/’My sessions’ pages, show additional information regarding the score, progress and/or certificate acquisition by the student."
            ],
            [
                "name" => "course_catalog_settings",
                "title" => "Course catalogue settings",
                "comment" => "This array gives you the possibility to configure many aspects of the course catalogue."
            ],
            [
                "name" => "resource_sequence_show_dependency_in_course_intro",
                "title" => "Show dependencies in course intro",
                "comment" => "When using resources sequencing with courses or sessions, show the dependencies of the course on the course’s homepage."
            ],
            [
                "name" => "course_catalog_display_in_home",
                "title" => "Display course catalogue on homepage",
                "comment" => ""
            ],
            [
                "name" => "course_creation_form_set_course_category_mandatory",
                "title" => "Set course category mandatory",
                "comment" => "When creating a course, make the course category a required setting."
            ],
            [
                "name" => "course_creation_form_hide_course_code",
                "title" => "Remove course code field from course creation form",
                "comment" => "If not provided, the course code is generated by default based on the course title, so enable this option to remove the code field from the course creation form altogether."
            ],
            [
                "name" => "course_about_teacher_name_hide",
                "title" => "Hide course teacher info on course details page",
                "comment" => "On the course details page, hide the teacher information."
            ],
            [
                "name" => "course_visibility_change_only_admin",
                "title" => "Course visibility changes for admins only",
                "comment" => "Remove the possibility for non-admins to change the course visibility. Visibility can be an issue when there are too many teachers to control directly. Forcing visibilities allows the organization to better manage courses catalogues."
            ],
            [
                "name" => "catalog_hide_public_link",
                "title" => "Hide course catalogue’s public link",
                "comment" => "Hides the link to the course catalogue in the menu when the catalogue is public."
            ],
            [
                "name" => "course_log_default_extra_fields",
                "title" => "User extra fields by default in course stats page",
                "comment" => "Configure this array with the internal IDs of the extra fields you want to show by default in the main course stats page."
            ],
            [
                "name" => "show_courses_in_catalogue",
                "title" => "Only show matching courses in catalogue",
                "comment" => "When enabled, only the courses with the extra field ‘show_in_catalogue’ set to 1 will appear in the catalogue."
            ],
            [
                "name" => "courses_catalogue_show_only_category",
                "title" => "Only show matching categories in courses catalogue",
                "comment" => "When not empty, only the courses from the given categories will appear in the courses catalogue."
            ],
            [
                "name" => "course_creation_by_teacher_extra_fields_to_show",
                "title" => "Extra fields to show on course creation form",
                "comment" => "The fields defined in this array will appear as additional fields in the course creation form."
            ],
            [
                "name" => "course_creation_form_set_extra_fields_mandatory",
                "title" => "Extra fields to require on course creation form",
                "comment" => "The fields defined in this array will be mandatory in the course creation form."
            ],
            [
                "name" => "course_configuration_tool_extra_fields_to_show_and_edit",
                "title" => "Extra fields to show in course settings",
                "comment" => "The fields defined in this array will appear on the course settings page."
            ],
            [
                "name" => "course_creation_user_course_extra_field_relation_to_prefill",
                "title" => "Prefill course fields with fields from user",
                "comment" => "If not empty, the course creation process will look for some fields in the user profile and auto-fill them for the course. For example, a teacher specialized in digital marketing could automatically set a « digital marketing » flag on each course (s)he creates."
            ],
            [
                "name" => "allow_edit_tool_visibility_in_session",
                "title" => "Allow tool visibility edition in sessions",
                "comment" => "When using sessions, the default behaviour is to use the tool visibility defined in the base course. This setting changes that to allow coaches in session courses to adapt tool visibilities to their needs."
            ],
            [
                "name" => "user_name_order",
                "title" => "Order of user lastname and firstname",
                "comment" => ""
            ],
            [
                "name" => "user_name_sort_by",
                "title" => "Sort users by specific info by default",
                "comment" => ""
            ],
            [
                "name" => "use_virtual_keyboard",
                "title" => "Use virtual keyboard",
                "comment" => "Make a virtual keyboard appear. This is useful when setting up restrictive exams in a physical room where students have no keyboard to limit their ability to cheat."
            ],
            [
                "name" => "disable_copy_paste",
                "title" => "Disable copy-pasting",
                "comment" => "When enabled, this option disables as well as possible the copy-pasting mechanisms. Useful in restrictive exams setups."
            ],
            [
                "name" => "bug_report_link",
                "title" => "Bug report link",
                "comment" => "Provide link to a bug reporting platform if not using the internal ticket manager."
            ],
            [
                "name" => "default_template",
                "title" => "Layout template",
                "comment" => "Provide the name of the template folder from main/template/ to change the appearance and structure of this portal."
            ],
            [
                "name" => "hide_social_media_links",
                "title" => "Hide social media links",
                "comment" => "Some pages allow you to promote the portal or a course on social networks. Enable this setting to remove the links."
            ],
            [
                "name" => "send_notification_when_document_added",
                "title" => "Send notification to students when document added",
                "comment" => "Whenever someone creates a new item in the documents tool, send a notification to users."
            ],
            [
                "name" => "thematic_pdf_orientation",
                "title" => "PDF orientation for course progress",
                "comment" => "In the course progress tool, you can print a PDF of the different elements. Set ‘portrait’ or ‘landscape’ (technical terms) to change it."
            ],
            [
                "name" => "certificate_pdf_orientation",
                "title" => "PDF orientation for certificates",
                "comment" => "Set ‘portrait’ or ‘landscape’ (technical terms) for PDF certificates."
            ],
            [
                "name" => "allow_general_certificate",
                "title" => "Enable general certificate",
                "comment" => "A general certificate is a certificate grouping all the accomplishments by the user in the courses (s)he followed."
            ],
            [
                "name" => "group_document_access",
                "title" => "Enable sharing options for group document",
                "comment" => ""
            ],
            [
                "name" => "group_category_document_access",
                "title" => "Enable sharing options for document inside group category",
                "comment" => ""
            ],
            [
                "name" => "allow_compilatio_tool",
                "title" => "Enable Compilatio",
                "comment" => "Compilatio is an anti-cheating service that compares text between two submissions and reports if there is a high probability the content (usually assignments) is not genuine."
            ],
            [
                "name" => "compilatio_tool",
                "title" => "Compilatio settings",
                "comment" => "Configure the Compilatio connection details here."
            ],
            [
                "name" => "documents_hide_download_icon",
                "title" => "Hide documents download icon",
                "comment" => "In the documents tool, hide the download icon from users."
            ],
            [
                "name" => "enable_x_sendfile_headers",
                "title" => "Enable X-sendfile headers",
                "comment" => "Enable this if you have X-sendfile enabled at the web server level and you want to add the required headers for browsers to pick it up."
            ],
            [
                "name" => "documents_custom_cloud_link_list",
                "title" => "Set strict hosts list for cloud links",
                "comment" => "The documents tool can integrate links to files in the cloud. The list of cloud services is limited to a hardcoded list, but you can define the ‘links’ array that will contain a list of your own list of services/URLs. The list defined here will replace the default list."
            ],
            [
                "name" => "translate_html",
                "title" => "Support multi-language HTML content",
                "comment" => "If enabled, this option allows users to use a ‘lang’ attribute in HTML elements to define the langage the content of that element is written in. Enable multiple elements with different ‘lang’ attributes and Chamilo will display the content in the langage of the user only."
            ],
            [
                "name" => "save_titles_as_html",
                "title" => "Save titles as HTML",
                "comment" => "Allow users to include HTML in title fields in several places. This allows for some styling of titles, notably in test questions."
            ],
            [
                "name" => "full_ckeditor_toolbar_set",
                "title" => "Full WYSIWYG editor toolbar",
                "comment" => "Show the full toolbar in all WYSIWYG editor boxes around the platform."
            ],
            [
                "name" => "ck_editor_block_image_copy_paste",
                "title" => "Prevent copy-pasting images in WYSIWYG editor",
                "comment" => "Prevent the use of images copy-paste as base64 in the editor to avoid filling the database with images."
            ],
            [
                "name" => "editor_driver_list",
                "title" => "List of WYSIWYG files drivers",
                "comment" => "Array containing the names of the drivers for files access from the WYSIWYG editor."
            ],
            [
                "name" => "enable_uploadimage_editor",
                "title" => "Allow images drag&drop in WYSIWYG editor",
                "comment" => "Enable image upload as file when doing a copy in the content or a drag and drop."
            ],
            [
                "name" => "editor_settings",
                "title" => "WYSIWYG editor settings",
                "comment" => "Generic configuration array to reconfigure the WYSIWYG editor globally."
            ],
            [
                "name" => "video_context_menu_hidden",
                "title" => "Hide the context menu on video player",
                "comment" => ""
            ],
            [
                "name" => "video_player_renderers",
                "title" => "Video player renderers",
                "comment" => "Enable player renderers for YouTube, Vimeo, Facebook, DailyMotion, Twitch medias"
            ],
            [
                "name" => "allow_edit_exercise_in_lp",
                "title" => "Allow teachers to edit tests in learning paths",
                "comment" => "By default, Chamilo prevents you from editing tests that are included inside a learning path. This is to avoid changes that would affect learners (past and future) differently regarding the results and/or progress in the learning path. This option allows teachers to bypass this restriction."
            ],
            [
                "name" => "exercise_hide_label",
                "title" => "Hide question ribbon (right/wrong) in test results",
                "comment" => "In test results, a ribbon appears by default to indicate if the answer was right or wrong. Enable this option to remove the ribbon globally."
            ],
            [
                "name" => "block_quiz_mail_notification_general_coach",
                "title" => "Block sending test notifications to general coach",
                "comment" => "Learners completing a test usually sends notifications to coaches, including the general session coach. Enable this option to omit the general coach from these notifications."
            ],
            [
                "name" => "allow_quiz_question_feedback",
                "title" => "Add question feedback if incorrect answer",
                "comment" => "By default, Chamilo allows you to show feedback on each answer in a question. With this option, an additional field is created to provide pre-defined feedback to the whole question. This feedback will only appear if the user answered incorrectly."
            ],
            [
                "name" => "allow_quiz_show_previous_button_setting",
                "title" => "Show 'previous' button in test to navigate questions",
                "comment" => "Set this to false to disable the 'previous' button when answering questions in a test, thus forcing users to always move ahead."
            ],
            [
                "name" => "allow_teacher_comment_audio",
                "title" => "Audio feedback to submitted answers",
                "comment" => "Allow teachers to provide feedback to users through audio (alternatively to text) on each question in a test."
            ],
            [
                "name" => "quiz_prevent_copy_paste",
                "title" => "Block copy-pasting in tests",
                "comment" => "Block copy/paste/save/print keys and right-clicks in exercises."
            ],
            [
                "name" => "quiz_show_description_on_results_page",
                "title" => "Always show test description on results page",
                "comment" => ""
            ],
            [
                "name" => "quiz_generate_certificate_ending",
                "title" => "Generate certificate on test end",
                "comment" => "Generate certificate when ending a quiz. The quiz needs to be linked in the gradebook tool and have a pass percentage configured."
            ],
            [
                "name" => "quiz_open_question_decimal_score",
                "title" => "Decimal score in open question types",
                "comment" => "Allow the teacher to rate the open, oral expression and annotation question types with a decimal score."
            ],
            [
                "name" => "quiz_check_button_enable",
                "title" => "Add answer-saving process check before test",
                "comment" => "Make sure users are all set to start the test by providing a simulation of the question-saving process before entering the test. This allows for early detection of some connection issues and reduces user experience frictions."
            ],
            [
                "name" => "allow_notification_setting_per_exercise",
                "title" => "Test notification settings at test-level",
                "comment" => "Enable the configuration of test submission notifications at the test level rather than the course level. Falls back to course-level settings if not defined at test-level."
            ],
            [
                "name" => "hide_free_question_score",
                "title" => "Hide open questions' score",
                "comment" => "Hide the fact that open questions (including audio and annotations) have a score by hiding the score display in all learner-facing reports."
            ],
            [
                "name" => "hide_user_info_in_quiz_result",
                "title" => "Hide user info on test results page",
                "comment" => "The default test results page shows a user datasheet (photo, name, etc) which might, in some contexts, be considered as pushing the limits of personal data treatment. Enable this option to remove user details from the test results."
            ],
            [
                "name" => "exercise_attempts_report_show_username",
                "title" => "Show username in test results page",
                "comment" => "Show the username (instead or, or as well as, the user info) on the test results page."
            ],
            [
                "name" => "allow_exercise_auto_launch",
                "title" => "Allow tests auto-launch",
                "comment" => "The auto-launch feature allows the teacher to set an exercise to open immediately upon accessing the course homepage. Enable this option and click on the rocket icon in the list of tests to enable."
            ],
            [
                "name" => "disable_clean_exercise_results_for_teachers",
                "title" => "Disable 'clean results' for teachers",
                "comment" => "Disable the option to delete test results from the tests list. This is often used when less-careful teachers manage courses, to avoid critical mistakes."
            ],
            [
                "name" => "show_exercise_question_certainty_ribbon_result",
                "title" => "Show score for certainty degree questions",
                "comment" => "By default, Chamilo does not show a score for the certainty degree question types."
            ],
            [
                "name" => "quiz_results_answers_report",
                "title" => "Show link to download test results",
                "comment" => "On the test results page, display a link to download the results as a file."
            ],
            [
                "name" => "send_score_in_exam_notification_mail_to_manager",
                "title" => "Add score in mail notification of test submission",
                "comment" => "Add the learner's score to the e-mail notification sent to the teacher after a test was submitted."
            ],
            [
                "name" => "show_exercise_expected_choice",
                "title" => "Show expected choice in test results",
                "comment" => "Show the expected choice and a status (right/wrong) for each answer on the test results page (if the test has been configured to show results)."
            ],
            [
                "name" => "exercise_category_round_score_in_export",
                "title" => "Round score in test exports",
                "comment" => ""
            ],
            [
                "name" => "exercises_disable_new_attempts",
                "title" => "Disable new test attempts",
                "comment" => "Disable new test attempts globally. Usually used when there is a problem with tests in general and you want some time to analyse without blocking the whole platform."
            ],
            [
                "name" => "show_question_id",
                "title" => "Show question IDs in tests",
                "comment" => "Show questions' internal IDs to let users take note of issues on specific questions and report them more efficiently."
            ],
            [
                "name" => "show_question_pagination",
                "title" => "Show question pagination for teachers",
                "comment" => "For tests with many questions, use pagination if the number of questions is higher than this setting. Set to 0 to prevent using pagination."
            ],
            [
                "name" => "question_pagination_length",
                "title" => "Question pagination length for teachers",
                "comment" => "Number of questions to show on every page when using the question pagination for teachers option."
            ],
            [
                "name" => "limit_exercise_teacher_access",
                "title" => "Limit teachers' permissions over tests",
                "comment" => "When enabled, teachers cannot delete tests nor questions, change tests visibility, download to QTI, clean results, etc."
            ],
            [
                "name" => "block_category_questions",
                "title" => "Lock questions of previous categories in a test",
                "comment" => "When using this option, an additional option will appear in the test's configuration. When using a test with multiple question categories and asking for a distribution by category, this will allow the user to navigate questions per category. Once a category is finished, (s)he moves to the next category and cannot return to the previous category."
            ],
            [
                "name" => "exercise_score_format",
                "title" => "Tests score format",
                "comment" => "Select between the following forms for the display of users' score in various reports: 1 = SCORE_AVERAGE (5 / 10); 2 = SCORE_PERCENT (50%); 3 = SCORE_DIV_PERCENT (5 / 10 (50%)). Use the numerical ID of the form you want to use."
            ],
            [
                "name" => "exercise_additional_teacher_modify_actions",
                "title" => "Additional links for teachers in tests list",
                "comment" => "Configure callback elements to generate new action icons for teachers to the right side of the tests list, in the form of an array, e.g. ['myplugin' => ['MyPlugin', 'urlGeneratorCallback']]"
            ],
            [
                "name" => "quiz_confirm_saved_answers",
                "title" => "Add checkbox for answers count confirmation",
                "comment" => "This option adds a checkbox at the end of each test asking the user to confirm the number of answers saved. This provides better auditing data for critical tests."
            ],
            [
                "name" => "allow_exercise_categories",
                "title" => "Enable test categories",
                "comment" => "Test categories are not enabled by default because they add a level of complexity. Enable this feature to show all test categories related management icons appear."
            ],
            [
                "name" => "allow_quiz_results_page_config",
                "title" => "Enable test results page configuration",
                "comment" => "Define an array of settings you want to apply to all tests results pages. Settings can be 'hide_question_score', 'hide_expected_answer', 'hide_category_table', 'hide_correct_answered_questions', 'hide_total_score' and possibly more in the future. Look for ‘getPageConfigurationAttribute’ in the code to see what’s in use."
            ],
            [
                "name" => "quiz_image_zoom",
                "title" => "Enable test images zooming",
                "comment" => "Enable this feature to allow users to zoom on images used in the tests."
            ],
            [
                "name" => "quiz_answer_extra_recording",
                "title" => "Enable extra test answers recording",
                "comment" => "Enable recording of all answers (even temporary) in the track_e_attempt_recording table. This feautre is experimentaland can create issues in the reporting pages when attempting to grade a test."
            ],
            [
                "name" => "allow_mandatory_question_in_category",
                "title" => "Enable selecting mandatory questions",
                "comment" => "Enable the selection of mandatory questions in a test when using random categories."
            ],
            [
                "name" => "add_exercise_best_attempt_in_report",
                "title" => "Enable display of best score attempt",
                "comment" => "Provide a list of courses and tests' IDs that will show the best score attempt for any learner in the reports. "
            ],
            [
                "name" => "exercise_category_report_user_extra_fields",
                "title" => "Add user extra fields in exercise category report",
                "comment" => "Define an array with the list of user extra fields to add to the report."
            ],
            [
                "name" => "score_grade_model",
                "title" => "Score grades model",
                "comment" => "Define an array of score ranges and colors to display reports using this model. This allows you to show colors rather than numerical grades."
            ],
            [
                "name" => "allow_time_per_question",
                "title" => "Enable time per question in tests",
                "comment" => "By default, it is only possible to limit the time per test. Limiting it per question adds an extra layer of possibilities, and you can (carefully) combine both."
            ],
            [
                "name" => "my_courses_show_pending_exercise_attempts",
                "title" => "Global pending tests list",
                "comment" => "Enable to display to the final user a page with the list of pending tests across all courses."
            ],
            [
                "name" => "allow_quick_question_description_popup",
                "title" => "Quick image addition to question",
                "comment" => "Enable an additional icon in the test questions list to add an image as question description. This vastly accelerates question edition when the questions are in the title and the description only includes an image."
            ],
        ];
    }
}
