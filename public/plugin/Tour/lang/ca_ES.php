<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Visita guiada';
$strings['plugin_comment'] = "Aquest plugin mostra a les persones com utilitzar el vostre Chamilo LMS. Heu d'<strong>activar</strong> una regió (p. ex. \"header-right\") per mostrar el botó que permet iniciar la visita guiada.";

/* Strings for settings */
$strings['show_tour'] = 'Mostrar la visita guiada';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = "La configuració necessària per mostrar els blocs d'ajuda, en format JSON, es troba al fitxer <strong>plugin/tour/config/tour.json</strong>. <br> Consulteu el fitxer README per obtenir més informació.";

$strings['theme'] = 'Tema';
$strings['theme_help'] = 'Trieu <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Deixeu-ho en blanc per utilitzar el tema predeterminat.';

/* Strings for plugin UI */
$strings['Skip'] = 'Ometre';
$strings['Next'] = 'Següent';
$strings['Prev'] = 'Anterior';
$strings['Done'] = 'Fet';
$strings['StartButtonText'] = 'Iniciar la visita guiada';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Benvinguts a <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Barra de menú amb enllaços a les seccions principals del portal';
$strings['TheRightPanelStep'] = 'Panell lateral';
$strings['TheUserImageBlock'] = 'La vostra foto de perfil';
$strings['TheProfileBlock'] = 'Els vostres eines de perfil: <i>Correu entrant</i>, <i>redactor de missatges</i>, <i>invitacions pendents</i>, <i>edició del perfil</i>.';
$strings['TheHomePageStep'] = "Aquesta és la pàgina principal inicial on trobareu els anuncis del portal, enllaços i qualsevol informació que l'equip d'administració hagi configurat.";

// if body class = section-mycourses
$strings['YourCoursesList'] = "Aquesta àrea mostra els diferents cursos (o sessions) als quals esteu subscrits. Si no es mostra cap curs, aneu al catàleg de cursos (vegeu el menú) o parleu amb l'administrador del vostre portal";

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = "L'eina d'agenda us permet veure quins esdeveniments estan programats per als propers dies, setmanes o mesos.";
$strings['AgendaTheActionBar'] = "Podeu decidir mostrar els esdeveniments com una llista, en lloc d'una vista de calendari, utilitzant les icones d'acció proporcionades";
$strings['AgendaTodayButton'] = "Feu clic al botó \"avui\" per veure només l'agenda d'avui";
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'El mes actual sempre es mostra destacat a la vista del calendari';
$strings['AgendaButtonsAllowYouToChangePeriod'] = "Podeu canviar la vista a diària, setmanal o mensual fent clic en un d'aquests botons";

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Aquesta àrea us permet consultar el vostre progrés si sou estudiant, o el progrés dels vostres estudiants si sou professor';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Els informes proporcionats en aquesta pantalla són extensibles i poden oferir-vos informació molt valuosa sobre el vostre aprenentatge o docència';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = "L'àrea social us permet posar-vos en contacte amb altres usuaris de la plataforma";
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = "El menú us dóna accés a una sèrie de pantalles que us permeten participar en missatgeria privada, xat, grups d'interès, etc.";

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'El Dashboard us permet obtenir informació molt específica en un format il·lustrat i condensat. Només els administradors tenen accés a aquesta funció en aquest moment';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = "Per activar els panells del Dashboard, primer heu d'activar els panells possibles a la secció d'administració per a plugins, després torneu aquí i trieu quins panells *vosaltres* voleu veure al vostre dashboard";

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = "El panell d'administració us permet gestionar tots els recursos del vostre portal Chamilo";
$strings['AdminUsersBlockAllowsYouToManageUsers'] = "El bloc d'usuaris us permet gestionar tot el que està relacionat amb els usuaris.";
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'El bloc de cursos us dóna accés a la creació, edició de cursos, etc. Altres blocs estan dedicats a usos específics també.';


$strings['tour_home_featured_courses_title'] = 'Cursos destacats';
$strings['tour_home_featured_courses_content'] = 'Aquesta secció mostra els cursos destacats disponibles a la vostra pàgina principal.';

$strings['tour_home_course_card_title'] = 'Targeta del curs';
$strings['tour_home_course_card_content'] = 'Cada targeta resumeix un curs i us dóna accés ràpid a la seva informació principal.';

$strings['tour_home_course_title_title'] = 'Títol del curs';
$strings['tour_home_course_title_content'] = 'El títol del curs us ajuda a identificar el curs ràpidament i també pot obrir més informació segons la configuració de la plataforma.';

$strings['tour_home_teachers_title'] = 'Professors';
$strings['tour_home_teachers_content'] = 'Aquesta àrea mostra els professors o usuaris associats al curs.';

$strings['tour_home_rating_title'] = 'Valoració i comentaris';
$strings['tour_home_rating_content'] = 'Aquí podeu consultar la valoració del curs i, quan està permès, emetre el vostre propi vot.';

$strings['tour_home_main_action_title'] = 'Acció principal del curs';
$strings['tour_home_main_action_content'] = "Utilitzeu aquest botó per entrar al curs, subscriure-us o revisar les restriccions d'accés segons l'estat del curs.";

$strings['tour_home_show_more_title'] = 'Mostrar més cursos';
$strings['tour_home_show_more_content'] = 'Utilitzeu aquest botó per carregar més cursos i continuar explorant el catàleg des de la pàgina principal.';

$strings['tour_my_courses_cards_title'] = 'Les vostres targetes de curs';
$strings['tour_my_courses_cards_content'] = 'Aquesta pàgina llista els cursos als quals esteu subscrits. Cada targeta us dóna accés ràpid al curs i al seu estat actual.';

$strings['tour_my_courses_image_title'] = 'Imatge del curs';
$strings['tour_my_courses_image_content'] = 'La imatge del curs us ajuda a identificar el curs ràpidament. En la majoria dels casos, fer clic obre el curs.';

$strings['tour_my_courses_title_title'] = 'Títol del curs i sessió';
$strings['tour_my_courses_title_content'] = 'Aquí podeu veure el títol del curs i, quan correspon, el nom de la sessió associada a aquest curs.';

$strings['tour_my_courses_progress_title'] = "Progrés d'aprenentatge";
$strings['tour_my_courses_progress_content'] = 'Aquesta barra de progrés mostra quant del curs heu completat.';

$strings['tour_my_courses_notifications_title'] = 'Notificacions de nou contingut';
$strings['tour_my_courses_notifications_content'] = "Utilitzeu aquest botó de campana per comprovar si el curs té nou contingut o actualitzacions recents. Quan està destacat, us ajuda a detectar ràpidament els canvis des de l'últim accés.";

$strings['tour_my_courses_footer_title'] = 'Professors i detalls del curs';
$strings['tour_my_courses_footer_content'] = 'El peu de pàgina pot mostrar professors, idioma i altra informació útil relacionada amb el curs.';

$strings['tour_my_courses_create_course_title'] = 'Crear un curs';
$strings['tour_my_courses_create_course_content'] = "Si teniu permís per crear cursos, utilitzeu aquest botó per obrir el formulari de creació de curs directament des d'aquesta pàgina.";

$strings['tour_course_home_header_title'] = 'Capçalera del curs';
$strings['tour_course_home_header_content'] = 'Aquesta capçalera mostra el títol del curs i, quan correspon, la sessió activa. També agrupa les accions principals del professor disponibles en aquesta pàgina.';

$strings['tour_course_home_title_title'] = 'Títol del curs';
$strings['tour_course_home_title_content'] = 'Aquí podeu identificar ràpidament el curs actual. Si el curs pertany a una sessió, es mostra el títol de la sessió al costat.';

$strings['tour_course_home_teacher_tools_title'] = 'Eines del professor';
$strings['tour_course_home_teacher_tools_content'] = "Segons els vostres permisos, aquesta àrea pot incloure l'intercanvi de vista d'estudiant, l'edició de la introducció, l'accés als informes i accions addicionals de gestió del curs.";

$strings['tour_course_home_intro_title'] = 'Introducció del curs';
$strings['tour_course_home_intro_content'] = 'Aquesta secció mostra la introducció del curs. Els professors poden utilitzar-la per presentar objectius, orientacions, enllaços o informació clau per als aprenents.';

$strings['tour_course_home_tools_controls_title'] = "Controls d'eines";
$strings['tour_course_home_tools_controls_content'] = "Els professors poden utilitzar aquests controls per mostrar o amagar totes les eines alhora o activar el mode d'ordenació per reorganitzar les eines del curs.";

$strings['tour_course_home_tools_title'] = 'Eines del curs';
$strings['tour_course_home_tools_content'] = "Aquesta àrea conté les principals eines del curs, com documents, itineraris d'aprenentatge, exercicis, fòrums i altres recursos disponibles en el curs.";

$strings['tour_course_home_tool_card_title'] = "Targeta d'eina";
$strings['tour_course_home_tool_card_content'] = "Cada targeta d'eina dóna accés a una eina del curs. Utilitzeu-la per entrar ràpidament a l'àrea seleccionada del curs.";

$strings['tour_course_home_tool_shortcut_title'] = "Accés directe a l'eina";
$strings['tour_course_home_tool_shortcut_content'] = "Feu clic a l'àrea de la icona per obrir directament l'eina del curs seleccionada.";

$strings['tour_course_home_tool_name_title'] = "Nom de l'eina";
$strings['tour_course_home_tool_name_content'] = "El títol identifica l'eina i també funciona com un enllaç d'accés directe.";

$strings['tour_course_home_tool_visibility_title'] = "Visibilitat de l'eina";
$strings['tour_course_home_tool_visibility_content'] = "Si esteu editant el curs, aquest botó us permet canviar ràpidament la visibilitat de l'eina per als aprenents.";
$strings['tour_admin_overview_title'] = "Tauler d'administració";
$strings['tour_admin_overview_content'] = "Aquesta pàgina centralitza les principals àrees d'administració de la plataforma, agrupades per tema de gestió.";

$strings['tour_admin_user_management_title'] = "Gestió d'usuaris";
$strings['tour_admin_user_management_content'] = "Des d'aquest bloc podeu gestionar usuaris registrats, crear comptes, importar o exportar llistes d'usuaris, editar usuaris, anonimitzar dades i gestionar classes.";

$strings['tour_admin_course_management_title'] = 'Gestió de cursos';
$strings['tour_admin_course_management_content'] = 'Aquest bloc us permet crear i gestionar cursos, importar o exportar llistes de cursos, organitzar categories, assignar usuaris a cursos i configurar camps i eines relacionades amb el curs.';

$strings['tour_admin_sessions_management_title'] = 'Gestió de sessions';
$strings['tour_admin_sessions_management_content'] = 'Aquí podeu gestionar sessions de formació, categories de sessió, importacions i exportacions, directors de RRHH, carreres, promoció i camps relacionats amb la sessió.';

$strings['tour_admin_platform_management_title'] = 'Gestió de la plataforma';
$strings['tour_admin_platform_management_content'] = "Utilitzeu aquest bloc per configurar la plataforma globalment, ajustar configuracions, gestionar anuncis, idiomes i altres opcions d'administració central.";

$strings['tour_admin_tracking_title'] = 'Seguiment';
$strings['tour_admin_tracking_content'] = "Aquesta àrea dóna accés a informes, estadístiques globals, analítiques d'aprenentatge i altres dades de seguiment de tota la plataforma.";

$strings['tour_admin_assessments_title'] = 'Avaluacions';
$strings['tour_admin_assessments_content'] = "Aquest bloc proporciona accés a les funcions d'administració relacionades amb les avaluacions disponibles a la plataforma.";
$strings['tour_admin_skills_title'] = 'Competències';
$strings['tour_admin_skills_content'] = "Aquest bloc us permet gestionar competències d'usuari, importacions de competències, classificacions, nivells i avaluacions relacionades amb competències.";

$strings['tour_admin_system_title'] = 'Sistema';
$strings['tour_admin_system_content'] = "Aquí podeu accedir a eines de manteniment del servidor i la plataforma, com l'estat del sistema, neteja de fitxers temporals, omplidor de dades, proves de correu electrònic i utilitats tècniques.";

$strings['tour_admin_rooms_title'] = 'Aules';
$strings['tour_admin_rooms_content'] = "Aquest bloc dóna accés a les funcions de gestió d'aules, incloent sucursals, aules i cerca de disponibilitat d'aules.";

$strings['tour_admin_security_title'] = 'Seguretat';
$strings['tour_admin_security_content'] = "Utilitzeu aquesta àrea per revisar els intents d'inici de sessió, informes relacionats amb la seguretat i eines addicionals de seguretat disponibles a la plataforma.";

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = "Aquest bloc proporciona referències oficials de Chamilo, guies d'usuari, fòrums, recursos d'instal·lació i enllaços a proveïdors de serveis i informació del projecte.";

$strings['tour_admin_health_check_title'] = 'Comprovació de salut';
$strings['tour_admin_health_check_content'] = "Aquesta àrea us ajuda a revisar l'estat tècnic de la plataforma listant comprovacions d'entorn, camins accessibles per escriptura i advertències importants d'instal·lació.";

$strings['tour_admin_version_check_title'] = 'Comprovació de versió';
$strings['tour_admin_version_check_content'] = 'Utilitzeu aquest bloc per registrar el vostre portal i activar les funcions de comprovació de versió i opcions de llistat públic de la plataforma.';

$strings['tour_admin_professional_support_title'] = 'Suport professional';
$strings['tour_admin_professional_support_content'] = 'Aquest bloc explica com contactar amb proveïdors oficials de Chamilo per consultoria, allotjament, formació i suport de desenvolupament personalitzat.';

$strings['tour_admin_news_title'] = 'Notícies de Chamilo';
$strings['tour_admin_news_content'] = 'Aquesta secció mostra notícies i anuncis recents del projecte Chamilo.';

$strings['tour_home_topbar_logo_title'] = 'Logotip de la plataforma';
$strings['tour_home_topbar_logo_content'] = 'Aquest logotip et porta de nou a la pàgina inicial de la plataforma.';
$strings['tour_home_topbar_actions_title'] = 'Accions ràpides';
$strings['tour_home_topbar_actions_content'] = 'Aquí pots trobar icones d’accés ràpid com la creació de cursos, l’ajuda guiada, els tiquets i els missatges, segons el teu rol.';
$strings['tour_home_menu_button_title'] = 'Botó del menú';
$strings['tour_home_menu_button_content'] = 'Fes servir aquest botó per obrir o tancar ràpidament el menú lateral.';
$strings['tour_home_sidebar_title'] = 'Menú principal';
$strings['tour_home_sidebar_content'] = 'Aquest menú lateral dona accés a les seccions principals de la plataforma, segons els teus permisos.';
$strings['tour_home_user_area_title'] = 'Àrea d’usuari';
$strings['tour_home_user_area_content'] = 'Aquí pots accedir al teu perfil, a les opcions personals i tancar la sessió.';
