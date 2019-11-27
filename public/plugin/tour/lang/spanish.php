<?php
/* For licensing terms, see /license.txt */
/**
 * Strings to spanish L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.tour
 */
$strings['plugin_title'] = 'Tour';
$strings['plugin_comment'] = 'Este plugin muestra a la gente cómo usar tu LMS. Es necesario activar una región (ej. header-right) para que aparezca el botón que le da inicio.';

/* Strings for settings */
$strings['show_tour'] = 'Mostrar el tour';

$showTourHelpLine01 = 'La configuración necesaria para mostrar los bloques de ayuda, en formato JSON, están localizadas en el archivo %splugin/tour/config/tour.json%s.';
$showTourHelpLine02 = 'Ver el archivo README para más información.';

$strings['show_tour_help'] = sprintf("$showTourHelpLine01 %s $showTourHelpLine02", "<strong>", "</strong>", "<br>");

$strings['theme'] = 'Tema';
$strings['theme_help'] = 'Elegir entre <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Vacío para usar el tema por defecto.';

/* Strings for plugin UI */
$strings['Skip'] = 'Saltar';
$strings['Next'] = 'Siguiente';
$strings['Prev'] = 'Anterior';
$strings['Done'] = 'Hecho';
$strings['StartButtonText'] = 'Empezar el recorrido';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Bienvenido/a en <b>Chamilo LMS</b>.';
$strings['TheNavbarStep'] = 'Barra de herramientas con enlaces a las principales secciones';
$strings['TheRightPanelStep'] = 'Pane lateral';
$strings['TheUserImageBlock'] = 'Tu foto de perfil.';
$strings['TheProfileBlock'] = 'Herramientas de perfil: <i>Bandeja de entrada</i>, <i>Nuevo mensaje</i>, <i>Invitaciones pendientes</i>, <i>Editar perfil</i>.';
$strings['TheHomePageStep'] = 'Esta es la página de inicio en la cual se encuentran los anuncios del portal, una zona de introducción, enlaces, etc, según lo que el equipo de administración ha configurado.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Esta zona muestra los distintos cursos (o sesiones) a los cuales tiene acceso. Si ningun curso aparece, puede dar una vuelta en el catálogo de cursos (ver menú) o conversarlo con su administrador de portal';

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
