<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240409120300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update configuration title and comment values in settings_current';
    }

    public function up(Schema $schema): void
    {
        $settingsToUpdate = [
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

        foreach ($settingsToUpdate as $settingData) {
            $variableExists = $this->connection->fetchOne(
                'SELECT COUNT(*) FROM settings_current WHERE variable = ?',
                [$settingData['name']]
            );

            if ($variableExists) {
                $this->addSql(
                    'UPDATE settings_current SET title = :title, comment = :comment WHERE variable = :name',
                    [
                        'title' => $settingData['title'],
                        'comment' => $settingData['comment'],
                        'name' => $settingData['name'],
                    ]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
