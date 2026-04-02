<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Rondleiding';
$strings['plugin_comment'] = 'Deze plugin toont mensen hoe ze uw Chamilo LMS kunnen gebruiken. U moet één regio activeren (bijv. "header-right") om de knop te tonen waarmee de rondleiding kan starten.';

/* Strings for settings */
$strings['show_tour'] = 'Toon de rondleiding';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'De noodzakelijke configuratie om de helpblokken te tonen, in JSON-formaat, bevindt zich in het <strong>plugin/tour/config/tour.json</strong> bestand. <br> Zie het README-bestand voor meer informatie.';

$strings['theme'] = 'Thema';
$strings['theme_help'] = 'Kies <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Leeg om het standaardthema te gebruiken.';

/* Strings for plugin UI */
$strings['Skip'] = 'Overslaan';
$strings['Next'] = 'Volgende';
$strings['Prev'] = 'Vorige';
$strings['Done'] = 'Klaar';
$strings['StartButtonText'] = 'Start de rondleiding';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Welkom bij <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Menubalk met links naar de hoofdsecties van de portal';
$strings['TheRightPanelStep'] = 'Zijbalkpaneel';
$strings['TheUserImageBlock'] = 'Uw profielfoto';
$strings['TheProfileBlock'] = 'Uw profieltools: <i>Inbox</i>, <i>berichtcomponist</i>, <i>lopende uitnodigingen</i>, <i>profielbewerken</i>.';
$strings['TheHomePageStep'] = 'Dit is de initiële startpagina waar u de portalmededelingen, links en alle informatie vindt die het administratieteam heeft geconfigureerd.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Dit gebied toont de verschillende cursussen (of sessies) waaraan u bent ingeschreven. Als er geen cursus wordt weergegeven, ga dan naar de cursuscatalogus (zie menu) of bespreek het met uw portalbeheerder.';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'De agenda-tool laat u zien welke gebeurtenissen gepland staan voor de komende dagen, weken of maanden.';
$strings['AgendaTheActionBar'] = 'U kunt ervoor kiezen om de gebeurtenissen als lijst te tonen in plaats van in een kalenderweergave, met behulp van de verstrekte actie-iconen.';
$strings['AgendaTodayButton'] = 'Klik op de knop "vandaag" om alleen de agenda van vandaag te zien.';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'De huidige maand wordt altijd prominent weergegeven in de kalenderweergave.';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'U kunt de weergave wijzigen naar dagelijks, wekelijks of maandelijks door op een van deze knoppen te klikken.';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Dit gebied laat u uw voortgang controleren als u student bent, of de voortgang van uw studenten als u docent bent.';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'De rapporten op dit scherm zijn uitbreidbaar en kunnen u waardevolle inzichten bieden in uw leren of lesgeven.';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'Het sociale gebied stelt u in staat om in contact te komen met andere gebruikers op het platform.';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'Het menu geeft u toegang tot een reeks schermen waarmee u kunt deelnemen aan privéberichten, chat, interessegroepen, enz.';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'Het Dashboard biedt u zeer specifieke informatie in een geïllustreerd en beknopt formaat. Alleen beheerders hebben momenteel toegang tot deze functie.';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'Om Dashboard-panelen in te schakelen, moet u eerst de mogelijke panelen activeren in het beheerdersgedeelte voor plugins, en vervolgens hier terugkomen en kiezen welke panelen *u* op uw dashboard wilt zien.';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'Het administratiepaneel stelt u in staat om alle resources in uw Chamilo-portal te beheren.';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'Het gebruikersblok stelt u in staat om alles met betrekking tot gebruikers te beheren.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'Het cursusblok geeft u toegang tot cursuscreatie, bewerking, enz. Andere blokken zijn ook gewijd aan specifieke toepassingen.';


$strings['tour_home_featured_courses_title'] = 'Uitgelichte cursussen';
$strings['tour_home_featured_courses_content'] = 'Deze sectie toont de uitgelichte cursussen die beschikbaar zijn op uw startpagina.';

$strings['tour_home_course_card_title'] = 'Cursuskaart';
$strings['tour_home_course_card_content'] = 'Elke kaart vat één cursus samen en geeft u snelle toegang tot de belangrijkste informatie.';

$strings['tour_home_course_title_title'] = 'Cursustitel';
$strings['tour_home_course_title_content'] = 'De cursustitel helpt u de cursus snel te identificeren en kan afhankelijk van de platforminstellingen meer informatie openen.';

$strings['tour_home_teachers_title'] = 'Docenten';
$strings['tour_home_teachers_content'] = 'Dit gebied toont de docenten of gebruikers die aan de cursus zijn gekoppeld.';

$strings['tour_home_rating_title'] = 'Beoordeling en feedback';
$strings['tour_home_rating_content'] = 'Hier kunt u de cursusbeoordeling bekijken en, indien toegestaan, uw eigen stem uitbrengen.';

$strings['tour_home_main_action_title'] = 'Hoofd cursusactie';
$strings['tour_home_main_action_content'] = 'Gebruik deze knop om de cursus te betreden, in te schrijven of toegangsbeperkingen te bekijken afhankelijk van de cursusstatus.';

$strings['tour_home_show_more_title'] = 'Toon meer cursussen';
$strings['tour_home_show_more_content'] = 'Gebruik deze knop om meer cursussen te laden en de catalogus vanaf de startpagina verder te verkennen.';

$strings['tour_my_courses_cards_title'] = 'Uw cursuskaarten';
$strings['tour_my_courses_cards_content'] = 'Deze pagina somt de cursussen op waaraan u bent ingeschreven. Elke kaart geeft u snelle toegang tot de cursus en de huidige status.';

$strings['tour_my_courses_image_title'] = 'Cursusafbeelding';
$strings['tour_my_courses_image_content'] = 'De cursusafbeelding helpt u de cursus snel te identificeren. In de meeste gevallen opent een klik de cursus.';

$strings['tour_my_courses_title_title'] = 'Cursus- en sessietitel';
$strings['tour_my_courses_title_content'] = 'Hier ziet u de cursustitel en, indien van toepassing, de sessienaam die aan die cursus is gekoppeld.';

$strings['tour_my_courses_progress_title'] = 'Leerprogressie';
$strings['tour_my_courses_progress_content'] = 'Deze voortgangsbalk toont hoeveel van de cursus u hebt voltooid.';

$strings['tour_my_courses_notifications_title'] = 'Meldingen nieuwe inhoud';
$strings['tour_my_courses_notifications_content'] = 'Gebruik deze belknop om te controleren of de cursus nieuwe inhoud of recente updates heeft. Als deze gemarkeerd is, helpt het u snel wijzigingen sinds uw laatste toegang te vinden.';

$strings['tour_my_courses_footer_title'] = 'Docenten en cursusdetails';
$strings['tour_my_courses_footer_content'] = 'De footer kan docenten, taal en andere nuttige informatie over de cursus tonen.';

$strings['tour_my_courses_create_course_title'] = 'Maak een cursus';
$strings['tour_my_courses_create_course_content'] = 'Als u toestemming hebt om cursussen te maken, gebruikt u deze knop om het cursusaanmaakformulier direct vanaf deze pagina te openen.';

$strings['tour_course_home_header_title'] = 'Cursusheader';
$strings['tour_course_home_header_content'] = 'Deze header toont de cursustitel en, indien van toepassing, de actieve sessie. Het groepeert ook de belangrijkste docentacties die op deze pagina beschikbaar zijn.';

$strings['tour_course_home_title_title'] = 'Cursustitel';
$strings['tour_course_home_title_content'] = 'Hier kunt u de huidige cursus snel identificeren. Als de cursus tot een sessie behoort, wordt de sessietitel ernaast weergegeven.';

$strings['tour_course_home_teacher_tools_title'] = 'Docenttools';
$strings['tour_course_home_teacher_tools_content'] = 'Afhankelijk van uw rechten kan dit gebied de studentweergave-schakelaar, introductiebewerking, rapporttoegang en extra cursusbeheeracties bevatten.';

$strings['tour_course_home_intro_title'] = 'Cursusintroductie';
$strings['tour_course_home_intro_content'] = 'Deze sectie toont de introductie van de cursus. Docenten kunnen deze gebruiken om doelstellingen, richtlijnen, links of belangrijke informatie voor leerlingen te presenteren.';

$strings['tour_course_home_tools_controls_title'] = 'Toolbesturing';
$strings['tour_course_home_tools_controls_content'] = 'Docenten kunnen deze besturingselementen gebruiken om alle tools tegelijk te tonen of te verbergen, of sorteermodus in te schakelen om de cursus-tools te herorganiseren.';

$strings['tour_course_home_tools_title'] = 'Cursus-tools';
$strings['tour_course_home_tools_content'] = 'Dit gebied bevat de belangrijkste cursus-tools, zoals documenten, leerpaden, oefeningen, forums en andere beschikbare bronnen in de cursus.';

$strings['tour_course_home_tool_card_title'] = 'Toolkaart';
$strings['tour_course_home_tool_card_content'] = 'Elke toolkaart geeft toegang tot één cursus-tool. Gebruik deze om snel het geselecteerde cursusgebied te betreden.';

$strings['tour_course_home_tool_shortcut_title'] = 'Tool-snelkoppeling';
$strings['tour_course_home_tool_shortcut_content'] = 'Klik op het pictogramgebied om de geselecteerde cursus-tool direct te openen.';

$strings['tour_course_home_tool_name_title'] = 'Toolnaam';
$strings['tour_course_home_tool_name_content'] = 'De titel identificeert de tool en fungeert ook als directe toegangslink.';

$strings['tour_course_home_tool_visibility_title'] = 'Toolzichtbaarheid';
$strings['tour_course_home_tool_visibility_content'] = 'Als u de cursus bewerkt, kunt u met deze knop de zichtbaarheid van de tool voor leerlingen snel wijzigen.';
$strings['tour_admin_overview_title'] = 'Beheerdashboard';
$strings['tour_admin_overview_content'] = 'Deze pagina centraliseert de belangrijkste beheergebieden van het platform, gegroepeerd per beheerthema.';

$strings['tour_admin_user_management_title'] = 'Gebruikersbeheer';
$strings['tour_admin_user_management_content'] = 'Vanuit dit blok kunt u geregistreerde gebruikers beheren, accounts aanmaken, gebruikerslijsten importeren of exporteren, gebruikers bewerken, gegevens anonimiseren en klassen beheren.';

$strings['tour_admin_course_management_title'] = 'Cursusbeheer';
$strings['tour_admin_course_management_content'] = 'Dit blok laat u cursussen aanmaken en beheren, cursuslijsten importeren of exporteren, categorieën organiseren, gebruikers aan cursussen toewijzen en cursusgerelateerde velden en tools configureren.';

$strings['tour_admin_sessions_management_title'] = 'Sessiesbeheer';
$strings['tour_admin_sessions_management_content'] = 'Hier kunt u trainingssessies beheren, sessiecategorieën, import en export, HR-directeuren, carrières, promoties en sessiegerelateerde velden.';

$strings['tour_admin_platform_management_title'] = 'Platformbeheer';
$strings['tour_admin_platform_management_content'] = 'Gebruik dit blok om het platform globaal te configureren, instellingen aan te passen, aankondigingen te beheren, talen en andere centrale beheermogelijkheden.';

$strings['tour_admin_tracking_title'] = 'Volgen';
$strings['tour_admin_tracking_content'] = 'Dit gebied geeft toegang tot rapporten, globale statistieken, leeranalyses en andere volggegevens over het hele platform.';

$strings['tour_admin_assessments_title'] = 'Beoordelingen';
$strings['tour_admin_assessments_content'] = 'Dit blok biedt toegang tot beoordelingsgerelateerde beheermogelijkheden die op het platform beschikbaar zijn.';
$strings['tour_admin_skills_title'] = 'Vaardigheden';
$strings['tour_admin_skills_content'] = 'Dit blok laat u gebruikersvaardigheden beheren, vaardigheidsimporten, ranglijsten, niveaus en beoordelingen met betrekking tot vaardigheden.';

$strings['tour_admin_system_title'] = 'Systeem';
$strings['tour_admin_system_content'] = 'Hier kunt u toegang krijgen tot server- en platformonderhoudstools, zoals systeembeschikbaarheid, tijdelijke bestandsopruiming, data-vuller, e-mailtests en technische hulpmiddelen.';

$strings['tour_admin_rooms_title'] = 'Lokalen';
$strings['tour_admin_rooms_content'] = 'Dit blok geeft toegang tot lokaalbeheerfuncties, inclusief filialen, lokalen en zoekopdrachten voor lokaalbeschikbaarheid.';

$strings['tour_admin_security_title'] = 'Beveiliging';
$strings['tour_admin_security_content'] = 'Gebruik dit gebied om inlogpogingen, beveiligingsgerelateerde rapporten en aanvullende beveiligingstools op het platform te bekijken.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Dit blok biedt officiële Chamilo-referenties, gebruikershandleidingen, forums, installatieruimtes en links naar serviceproviders en projectinformatie.';

$strings['tour_admin_health_check_title'] = 'Gezondheidscontrole';
$strings['tour_admin_health_check_content'] = 'Dit gebied helpt u de technische gezondheid van het platform te controleren door omgevingscontroles, beschrijf bare paden en belangrijke installatie waarschuwingen te tonen.';

$strings['tour_admin_version_check_title'] = 'Versiecontrole';
$strings['tour_admin_version_check_content'] = 'Gebruik dit blok om uw portaal te registreren en versiecontrolefuncties en openbare platformlijstopties in te schakelen.';

$strings['tour_admin_professional_support_title'] = 'Professionele ondersteuning';
$strings['tour_admin_professional_support_content'] = 'Dit blok legt uit hoe u officiële Chamilo-providers kunt contacteren voor advies, hosting, training en ondersteuning voor maatwerkontwikkeling.';

$strings['tour_admin_news_title'] = 'Nieuws van Chamilo';
$strings['tour_admin_news_content'] = 'Deze sectie toont recente nieuwsberichten en aankondigingen van het Chamilo-project.';
