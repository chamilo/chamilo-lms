<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Tour';
$strings['plugin_comment'] = 'This plugin shows people how to use your Chamilo LMS. You must activar one region (e.g. "header-right") to show the button that allows the tour to start.';

/* Strings for settings */
$strings['show_tour'] = 'Show the tour';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = sprintf("$showTourHelpLine01 %s $showTourHelpLine02", '<strong>', '</strong>', '<br>');

$strings['theme'] = 'Theme';
$strings['theme_help'] = 'Chose <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Empty to use the default theme.';

/* Strings for plugin UI */
$strings['Skip'] = 'Skip';
$strings['Next'] = 'Next';
$strings['Prev'] = 'Prev';
$strings['Done'] = 'Done';
$strings['StartButtonText'] = 'Start the tour';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Welcome to <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Menu bar with links to the main sections of the portal';
$strings['TheRightPanelStep'] = 'Sidebar panel';
$strings['TheUserImageBlock'] = 'Your profile photo';
$strings['TheProfileBlock'] = 'Your profile tools: <i>Inbox</i>, <i>message composer</i>, <i>pending invitations</i>, <i>profile edition</i>.';
$strings['TheHomePageStep'] = 'This is the initial homepage where you will find the portal announcements, links and any information the administration team has configured.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'This area shows the different courses (or sessions) to which you are subscribed. If no course shows, go to the course catalogue (see menu) or discuss it with your portal administrator';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'The agenda tool allows you to see what events are scheduled for the upcoming days, weeks or months.';
$strings['AgendaTheActionBar'] = 'You can decide to show the events as a list, rather than in a calendar view, using the action icons provided';
$strings['AgendaTodayButton'] = 'Click the "today" button to see only today\'s schedule';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'The current month is always shown in evidence in the calendar view';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'You can switch the view to daily, weekly or monthly by clicking one of these buttons';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'This area allows you to check your progress if you\'re a student, or the progress of your students if you are a teacher';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'The reports provided on this screen are extensible and can provide you very valuable insight on your learning or teaching';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'The social area allows you to get in touch with other users on the platform';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'The menu gives you access to a series of screens allowing you to participate in private messaging, chat, interest groups, etc';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'The Dashboard allows you to get very specific information in an illustrated and condensed format. Only administrators have access to this feature at this time';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'To enable Dashboard panels, you must first activate the possible panels in the admin section for plugins, then come back here and choose which panels *you* want to see on your dashboard';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'The administration panel allows you to manage all resources in your Chamilo portal';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'The users block allows you to manage all things related to users.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'The courses block gives you access to course creation, edition, etc. Other blocks are dedicated to specific uses as well.';


$strings['tour_home_featured_courses_title'] = 'Featured courses';
$strings['tour_home_featured_courses_content'] = 'This section shows the featured courses available on your home page.';

$strings['tour_home_course_card_title'] = 'Course card';
$strings['tour_home_course_card_content'] = 'Each card summarizes one course and gives you quick access to its main information.';

$strings['tour_home_course_title_title'] = 'Course title';
$strings['tour_home_course_title_content'] = 'The course title helps you identify the course quickly and may also open more information depending on the platform settings.';

$strings['tour_home_teachers_title'] = 'Teachers';
$strings['tour_home_teachers_content'] = 'This area shows the teachers or users associated with the course.';

$strings['tour_home_rating_title'] = 'Rating and feedback';
$strings['tour_home_rating_content'] = 'Here you can review the course rating and, when allowed, submit your own vote.';

$strings['tour_home_main_action_title'] = 'Main course action';
$strings['tour_home_main_action_content'] = 'Use this button to enter the course, subscribe, or review access restrictions depending on the course status.';

$strings['tour_home_show_more_title'] = 'Show more courses';
$strings['tour_home_show_more_content'] = 'Use this button to load more courses and continue exploring the catalogue from the home page.';

$strings['tour_my_courses_cards_title'] = 'Your course cards';
$strings['tour_my_courses_cards_content'] = 'This page lists the courses you are subscribed to. Each card gives you quick access to the course and its current status.';

$strings['tour_my_courses_image_title'] = 'Course image';
$strings['tour_my_courses_image_content'] = 'The course image helps you identify the course quickly. In most cases, clicking it opens the course.';

$strings['tour_my_courses_title_title'] = 'Course and session title';
$strings['tour_my_courses_title_content'] = 'Here you can see the course title and, when applicable, the session name associated with that course.';

$strings['tour_my_courses_progress_title'] = 'Learning progress';
$strings['tour_my_courses_progress_content'] = 'This progress bar shows how much of the course you have completed.';

$strings['tour_my_courses_notifications_title'] = 'New content notifications';
$strings['tour_my_courses_notifications_content'] = 'Use this bell button to check whether the course has new content or recent updates. When highlighted, it helps you quickly spot changes since your last access.';

$strings['tour_my_courses_footer_title'] = 'Teachers and course details';
$strings['tour_my_courses_footer_content'] = 'The footer can show teachers, language, and other useful information related to the course.';

$strings['tour_my_courses_create_course_title'] = 'Create a course';
$strings['tour_my_courses_create_course_content'] = 'If you have permission to create courses, use this button to open the course creation form directly from this page.';

$strings['tour_course_home_header_title'] = 'Course header';
$strings['tour_course_home_header_content'] = 'This header shows the course title and, when applicable, the active session. It also groups the main teacher actions available on this page.';

$strings['tour_course_home_title_title'] = 'Course title';
$strings['tour_course_home_title_content'] = 'Here you can identify the current course quickly. If the course belongs to a session, the session title is displayed next to it.';

$strings['tour_course_home_teacher_tools_title'] = 'Teacher tools';
$strings['tour_course_home_teacher_tools_content'] = 'Depending on your permissions, this area may include the student view switch, introduction editing, reporting access, and additional course management actions.';

$strings['tour_course_home_intro_title'] = 'Course introduction';
$strings['tour_course_home_intro_content'] = 'This section displays the introduction of the course. Teachers can use it to present objectives, guidance, links, or key information for learners.';

$strings['tour_course_home_tools_controls_title'] = 'Tools controls';
$strings['tour_course_home_tools_controls_content'] = 'Teachers can use these controls to show or hide all tools at once, or enable sorting mode to reorganize the course tools.';

$strings['tour_course_home_tools_title'] = 'Course tools';
$strings['tour_course_home_tools_content'] = 'This area contains the main course tools, such as documents, learning paths, exercises, forums and other resources available in the course.';

$strings['tour_course_home_tool_card_title'] = 'Tool card';
$strings['tour_course_home_tool_card_content'] = 'Each tool card gives access to one course tool. Use it to enter the selected area of the course quickly.';

$strings['tour_course_home_tool_shortcut_title'] = 'Tool shortcut';
$strings['tour_course_home_tool_shortcut_content'] = 'Click the icon area to open the selected course tool directly.';

$strings['tour_course_home_tool_name_title'] = 'Tool name';
$strings['tour_course_home_tool_name_content'] = 'The title identifies the tool and also works as a direct access link.';

$strings['tour_course_home_tool_visibility_title'] = 'Tool visibility';
$strings['tour_course_home_tool_visibility_content'] = 'If you are editing the course, this button lets you quickly change the visibility of the tool for learners.';
$strings['tour_admin_overview_title'] = 'Administration dashboard';
$strings['tour_admin_overview_content'] = 'This page centralizes the main administration areas of the platform, grouped by management topic.';

$strings['tour_admin_user_management_title'] = 'User management';
$strings['tour_admin_user_management_content'] = 'From this block you can manage registered users, create accounts, import or export user lists, edit users, anonymize data and manage classes.';

$strings['tour_admin_course_management_title'] = 'Course management';
$strings['tour_admin_course_management_content'] = 'This block lets you create and manage courses, import or export course lists, organize categories, assign users to courses and configure course-related fields and tools.';

$strings['tour_admin_sessions_management_title'] = 'Sessions management';
$strings['tour_admin_sessions_management_content'] = 'Here you can manage training sessions, session categories, imports and exports, HR directors, careers, promotions and session-related fields.';

$strings['tour_admin_platform_management_title'] = 'Platform management';
$strings['tour_admin_platform_management_content'] = 'Use this block to configure the platform globally, adjust settings, manage announcements, languages and other central administration options.';

$strings['tour_admin_tracking_title'] = 'Tracking';
$strings['tour_admin_tracking_content'] = 'This area gives access to reports, global statistics, learning analytics and other tracking data across the platform.';

$strings['tour_admin_assessments_title'] = 'Assessments';
$strings['tour_admin_assessments_content'] = 'This block provides access to assessment-related administration features available on the platform.';
$strings['tour_admin_skills_title'] = 'Skills';
$strings['tour_admin_skills_content'] = 'This block lets you manage user skills, skill imports, rankings, levels and assessments related to skills.';

$strings['tour_admin_system_title'] = 'System';
$strings['tour_admin_system_content'] = 'Here you can access server and platform maintenance tools, such as system status, temporary file cleanup, data filler, e-mail tests and technical utilities.';

$strings['tour_admin_rooms_title'] = 'Rooms';
$strings['tour_admin_rooms_content'] = 'This block gives access to room management features, including branches, rooms and room availability search.';

$strings['tour_admin_security_title'] = 'Security';
$strings['tour_admin_security_content'] = 'Use this area to review login attempts, security-related reports and additional security tools available on the platform.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'This block provides official Chamilo references, user guides, forums, installation resources and links to service providers and project information.';

$strings['tour_admin_health_check_title'] = 'Health check';
$strings['tour_admin_health_check_content'] = 'This area helps you review the technical health of the platform by listing environment checks, writable paths and important installation warnings.';

$strings['tour_admin_version_check_title'] = 'Version check';
$strings['tour_admin_version_check_content'] = 'Use this block to register your portal and enable version checking features and public platform listing options.';

$strings['tour_admin_professional_support_title'] = 'Professional support';
$strings['tour_admin_professional_support_content'] = 'This block explains how to contact official Chamilo providers for consulting, hosting, training and custom development support.';

$strings['tour_admin_news_title'] = 'News from Chamilo';
$strings['tour_admin_news_content'] = 'This section displays recent news and announcements from the Chamilo project.';

$strings['tour_home_topbar_logo_title'] = 'Platform logo';
$strings['tour_home_topbar_logo_content'] = 'This logo takes you back to the platform homepage.';
$strings['tour_home_topbar_actions_title'] = 'Quick actions';
$strings['tour_home_topbar_actions_content'] = 'Here you can find shortcut icons such as course creation, guided help, tickets and messages, depending on your role.';
$strings['tour_home_menu_button_title'] = 'Menu button';
$strings['tour_home_menu_button_content'] = 'Use this button to open or close the side menu quickly.';
$strings['tour_home_sidebar_title'] = 'Main menu';
$strings['tour_home_sidebar_content'] = 'This side menu gives access to the main platform sections, depending on your permissions.';
$strings['tour_home_user_area_title'] = 'User area';
$strings['tour_home_user_area_content'] = 'Here you can access your profile, personal options and sign out.';
