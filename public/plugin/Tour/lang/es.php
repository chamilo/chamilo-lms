<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to spanish L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Tour';
$strings['plugin_comment'] = 'Este plugin muestra a la gente cómo usar tu LMS. Es necesario activar una región (ej. header-right) para que aparezca el botón que le da inicio.';

/* Strings for settings */
$strings['show_tour'] = 'Mostrar el tour';

$showTourHelpLine01 = 'La configuración necesaria para mostrar los bloques de ayuda, en formato JSON, están localizadas en el archivo %splugin/tour/config/tour.json%s.';
$showTourHelpLine02 = 'Ver el archivo README para más información.';

$strings['show_tour_help'] = sprintf("$showTourHelpLine01 %s $showTourHelpLine02", '<strong>', '</strong>', '<br>');

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
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'The Dashboard allows you to get very specific information in an illustrated and condensed format. Only administrators have access to this feature at this time';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'To enable Dashboard panels, you must first activate the possible panels in the admin section for plugins, then come back here and choose which panels *you* want to see on your dashboard';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'The administration panel allows you to manage all resources in your Chamilo portal';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'The users block allows you to manage all things related to users.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'The courses block gives you access to course creation, edition, etc. Other blocks are dedicated to specific uses as well.';

$strings['tour_home_featured_courses_title'] = 'Cursos destacados';
$strings['tour_home_featured_courses_content'] = 'Esta sección muestra los cursos destacados disponibles en tu página de inicio.';
$strings['tour_home_course_card_title'] = 'Tarjeta del curso';
$strings['tour_home_course_card_content'] = 'Cada tarjeta resume un curso y te da acceso rápido a su información principal.';
$strings['tour_home_course_title_title'] = 'Título del curso';
$strings['tour_home_course_title_content'] = 'El título del curso te ayuda a identificarlo rápidamente y también puede abrir más información según la configuración de la plataforma.';
$strings['tour_home_teachers_title'] = 'Profesores';
$strings['tour_home_teachers_content'] = 'Esta área muestra los profesores o usuarios asociados al curso.';
$strings['tour_home_rating_title'] = 'Valoración y comentarios';
$strings['tour_home_rating_content'] = 'Aquí puedes revisar la valoración del curso y, cuando esté permitido, enviar tu propio voto.';
$strings['tour_home_main_action_title'] = 'Acción principal del curso';
$strings['tour_home_main_action_content'] = 'Usa este botón para entrar al curso, inscribirte o revisar restricciones de acceso según el estado del curso.';
$strings['tour_home_show_more_title'] = 'Mostrar más cursos';
$strings['tour_home_show_more_content'] = 'Usa este botón para cargar más cursos y seguir explorando el catálogo desde la página de inicio.';
$strings['tour_my_courses_cards_title'] = 'Tus tarjetas de curso';
$strings['tour_my_courses_cards_content'] = 'Esta página lista los cursos en los que estás inscrito. Cada tarjeta te da acceso rápido al curso y a su estado actual.';
$strings['tour_my_courses_image_title'] = 'Imagen del curso';
$strings['tour_my_courses_image_content'] = 'La imagen del curso te ayuda a identificarlo rápidamente. En la mayoría de los casos, al hacer clic se abre el curso.';
$strings['tour_my_courses_title_title'] = 'Título del curso y de la sesión';
$strings['tour_my_courses_title_content'] = 'Aquí puedes ver el título del curso y, cuando aplique, el nombre de la sesión asociada a ese curso.';
$strings['tour_my_courses_progress_title'] = 'Progreso de aprendizaje';
$strings['tour_my_courses_progress_content'] = 'Esta barra de progreso muestra cuánto del curso has completado.';
$strings['tour_my_courses_notifications_title'] = 'Notificaciones de contenido nuevo';
$strings['tour_my_courses_notifications_content'] = 'Usa este botón de campana para revisar si el curso tiene contenido nuevo o actualizaciones recientes. Cuando está resaltado, te ayuda a detectar rápidamente cambios desde tu último acceso.';
$strings['tour_my_courses_footer_title'] = 'Profesores y detalles del curso';
$strings['tour_my_courses_footer_content'] = 'El pie puede mostrar profesores, idioma y otra información útil relacionada con el curso.';
$strings['tour_my_courses_create_course_title'] = 'Crear un curso';
$strings['tour_my_courses_create_course_content'] = 'Si tienes permiso para crear cursos, usa este botón para abrir directamente el formulario de creación de cursos desde esta página.';
$strings['tour_course_home_header_title'] = 'Encabezado del curso';
$strings['tour_course_home_header_content'] = 'Este encabezado muestra el título del curso y, cuando corresponde, la sesión activa. También agrupa las acciones principales del profesor disponibles en esta página.';
$strings['tour_course_home_title_title'] = 'Título del curso';
$strings['tour_course_home_title_content'] = 'Aquí puedes identificar rápidamente el curso actual. Si el curso pertenece a una sesión, el título de la sesión se muestra junto a él.';
$strings['tour_course_home_teacher_tools_title'] = 'Herramientas del profesor';
$strings['tour_course_home_teacher_tools_content'] = 'Según tus permisos, esta área puede incluir el cambio a vista de estudiante, la edición de la introducción, el acceso a reportes y otras acciones de gestión del curso.';
$strings['tour_course_home_intro_title'] = 'Introducción del curso';
$strings['tour_course_home_intro_content'] = 'Esta sección muestra la introducción del curso. Los profesores pueden usarla para presentar objetivos, orientaciones, enlaces o información clave para los estudiantes.';
$strings['tour_course_home_tools_controls_title'] = 'Controles de herramientas';
$strings['tour_course_home_tools_controls_content'] = 'Los profesores pueden usar estos controles para mostrar u ocultar todas las herramientas a la vez, o activar el modo de ordenamiento para reorganizar las herramientas del curso.';
$strings['tour_course_home_tools_title'] = 'Herramientas del curso';
$strings['tour_course_home_tools_content'] = 'Esta área contiene las principales herramientas del curso, como documentos, rutas de aprendizaje, ejercicios, foros y otros recursos disponibles dentro del curso.';
$strings['tour_course_home_tool_card_title'] = 'Tarjeta de herramienta';
$strings['tour_course_home_tool_card_content'] = 'Cada tarjeta da acceso a una herramienta del curso. Úsala para entrar rápidamente al área seleccionada del curso.';
$strings['tour_course_home_tool_shortcut_title'] = 'Acceso rápido a la herramienta';
$strings['tour_course_home_tool_shortcut_content'] = 'Haz clic en el área del ícono para abrir directamente la herramienta seleccionada del curso.';
$strings['tour_course_home_tool_name_title'] = 'Nombre de la herramienta';
$strings['tour_course_home_tool_name_content'] = 'El título identifica la herramienta y también funciona como enlace de acceso directo.';
$strings['tour_course_home_tool_visibility_title'] = 'Visibilidad de la herramienta';
$strings['tour_course_home_tool_visibility_content'] = 'Si estás editando el curso, este botón te permite cambiar rápidamente la visibilidad de la herramienta para los estudiantes.';
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
$strings['tour_admin_skills_title'] = 'Competencias';
$strings['tour_admin_skills_content'] = 'Este bloque te permite gestionar las competencias de los usuarios, importaciones de competencias, rankings, niveles y evaluaciones relacionadas con competencias.';
$strings['tour_admin_system_title'] = 'Sistema';
$strings['tour_admin_system_content'] = 'Aquí puedes acceder a herramientas de mantenimiento del servidor y de la plataforma, como estado del sistema, limpieza de archivos temporales, llenado de datos, pruebas de correo y utilidades técnicas.';
$strings['tour_admin_rooms_title'] = 'Salas';
$strings['tour_admin_rooms_content'] = 'Este bloque da acceso a funcionalidades de gestión de salas, incluyendo sedes, salas y búsqueda de disponibilidad de salas.';
$strings['tour_admin_security_title'] = 'Seguridad';
$strings['tour_admin_security_content'] = 'Usa esta área para revisar intentos de inicio de sesión, reportes relacionados con seguridad y otras herramientas de seguridad disponibles en la plataforma.';
$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Este bloque proporciona referencias oficiales de Chamilo, guías de usuario, foros, recursos de instalación y enlaces a proveedores de servicios e información del proyecto.';
$strings['tour_admin_health_check_title'] = 'Chequeo de salud';
$strings['tour_admin_health_check_content'] = 'Esta área te ayuda a revisar la salud técnica de la plataforma mostrando verificaciones del entorno, rutas con permisos de escritura y advertencias importantes de instalación.';
$strings['tour_admin_version_check_title'] = 'Verificación de versión';
$strings['tour_admin_version_check_content'] = 'Usa este bloque para registrar tu portal y habilitar funciones de verificación de versión y opciones de listado público de la plataforma.';
$strings['tour_admin_professional_support_title'] = 'Soporte profesional';
$strings['tour_admin_professional_support_content'] = 'Este bloque explica cómo contactar a proveedores oficiales de Chamilo para consultoría, hosting, formación y soporte de desarrollos personalizados.';
$strings['tour_admin_news_title'] = 'Noticias de Chamilo';
$strings['tour_admin_news_content'] = 'Esta sección muestra noticias y anuncios recientes del proyecto Chamilo.';

$strings['tour_home_topbar_logo_title'] = 'Logo de la plataforma';
$strings['tour_home_topbar_logo_content'] = 'Este logo te lleva de vuelta a la página principal de la plataforma.';
$strings['tour_home_topbar_actions_title'] = 'Acciones rápidas';
$strings['tour_home_topbar_actions_content'] = 'Aquí puedes encontrar iconos de acceso rápido como creación de cursos, ayuda guiada, tickets y mensajes, según tu rol.';
$strings['tour_home_menu_button_title'] = 'Botón del menú';
$strings['tour_home_menu_button_content'] = 'Usa este botón para abrir o cerrar rápidamente el menú lateral.';
$strings['tour_home_sidebar_title'] = 'Menú principal';
$strings['tour_home_sidebar_content'] = 'Este menú lateral da acceso a las secciones principales de la plataforma, según tus permisos.';
$strings['tour_home_user_area_title'] = 'Área de usuario';
$strings['tour_home_user_area_content'] = 'Aquí puedes acceder a tu perfil, opciones personales y cerrar sesión.';
