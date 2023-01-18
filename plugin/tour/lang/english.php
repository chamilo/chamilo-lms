<?php
/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.tour
 */
$strings['plugin_title'] = 'Tour';
$strings['plugin_comment'] = 'This plugin shows people how to use your Chamilo LMS. You must activate the plugin in one region (e.g. "header-right") to show the button that allows the tour to start.';

/* Strings for settings */
$strings['show_tour'] = 'Show the tour';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = sprintf("$showTourHelpLine01 %s $showTourHelpLine02", "<strong>", "</strong>", "<br>");

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
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'The dashboard allows you to get very specific information in an illustrated and condensed format. Only administrators have access to this feature at this time';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'To enable dashboard panels, you must first activate the possible panels in the admin section for plugins, then come back here and choose which panels *you* want to see on your dashboard';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'The administration panel allows you to manage all resources in your Chamilo portal';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'The users block allows you to manage all things related to users.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'The courses block gives you access to course creation, edition, etc. Other blocks are dedicated to specific uses as well.';
