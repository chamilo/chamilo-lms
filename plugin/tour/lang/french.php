<?php
/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.tour
 */
$strings['plugin_title'] = 'Tour guidé';
$strings['plugin_comment'] = 'Ce plugin montre aux utilisateurs comment utiliser votre portail Chamilo. Vous devez activer une région (p.ex. "header-right") afin d\'afficher le bouton qui permet de démarrer le processus.';

/* Strings for settings */
$strings['show_tour'] = 'Activer le tour guidé';

$showTourHelpLine01 = 'La configuration nécessaire à l\'affichage du bloc d\'aide, au format JSON, se situe dans le fichier %splugin/tour/config/tour.json%s.';
$showTourHelpLine02 = 'Voir fichier README pour plus d\'info.';

$strings['show_tour_help'] = sprintf("$showTourHelpLine01 %s $showTourHelpLine02", "<strong>", "</strong>", "<br>");

$strings['theme'] = 'Thème';
$strings['theme_help'] = 'Choisissez entre <i>nassim</i>, <i>nazanin</i> et <i>royal</i>. Vide pour utiliser le thème par défaut.';

/* Strings for plugin UI */
$strings['Skip'] = 'Passer';
$strings['Next'] = 'Suivant';
$strings['Prev'] = 'Précédent';
$strings['Done'] = 'Terminé';
$strings['StartButtonText'] = 'Démarrer le tour guidé';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Bienvenu(e) dans <b>Chamilo LMS</b>';
$strings['TheNavbarStep'] = 'Barre de menu, reprenant les sections principales.';
$strings['TheRightPanelStep'] = 'Panneau latéral de menus';
$strings['TheUserImageBlock'] = 'Votre photo de profil utilisateur';
$strings['TheProfileBlock'] = 'Vos outils perso: <i>Boîte de messages</i>, <i>Composer des messages</i>, <i>Invitations en attente</i>, <i>Édition du profil</i>.';
$strings['TheHomePageStep'] = 'Ceci est la page d\'accueil du portail. On y retrouve les annonces du portail, une section d\'introduction, des liens, etc, selon ce que l\'équipe d\'administration a préparé';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Cette zone affiche les différents cours (ou sessions) auxquels vous avez accès. Si aucun cours ne s\'affiche, rendez-vous sur le catalogue de cours (voir menu) ou parlez-en à votre administrateur de portail';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'The agenda tool allows you to see what events are scheduled for the upcoming days, weeks or months.';
$strings['AgendaTheActionBar'] = 'You can decide to show the events as a list, rather than in a calendar view, using the action icons provided';
$strings['AgendaTodayButton'] = 'Click the "today" button to see only today\'s schedule';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'The current month is always shown in evidence in the calendar view';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'You can switch the view to daily, weekly or monthly by clicking one of these buttons';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'This area allows you to check your progress if you\'re a student, or the progress of your students if you are a teacher';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'The reports provided on this screen are extensible and can provide you very valuable insight on your learning or teaching';

// if body class = section-social
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'The social area allows you to get in touch with other users on the platform';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'The menu gives you access to a series of screens allowing you to participate in private messaging, chat, interest groups, etc';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'The dashboard allows you to get very specific information in an illustrated and condensed format. Only administrators have access to this feature at this time';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'To enable dashboard panels, you must first activate the possible panels in the admin section for plugins, then come back here and choose which panels *you* want to see on your dashboard';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'The administration panel allows you to manage all resources in your Chamilo portal';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'The users block allows you to manage all things related to users.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'The courses block gives you access to course creation, edition, etc. Other blocks are dedicated to specific uses as well.';
