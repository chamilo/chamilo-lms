<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Turné';
$strings['plugin_comment'] = 'Detta plugin visar hur man använder din Chamilo LMS. Du måste aktivera en region (t.ex. "header-right") för att visa knappen som startar turnén.';

/* Strings for settings */
$strings['show_tour'] = 'Visa turnén';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'Den nödvändiga konfigurationen för att visa hjälpblocken, i JSON-format, finns i filen <strong>plugin/tour/config/tour.json</strong>. <br> Se README-filen för mer information.';

$strings['theme'] = 'Tema';
$strings['theme_help'] = 'Välj <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Tomt för att använda standardtemat.';

/* Strings for plugin UI */
$strings['Skip'] = 'Hoppa över';
$strings['Next'] = 'Nästa';
$strings['Prev'] = 'Föregående';
$strings['Done'] = 'Färdig';
$strings['StartButtonText'] = 'Starta turnén';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Välkommen till <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Menyfält med länkar till portens huvudsidor';
$strings['TheRightPanelStep'] = 'Sidelpanel';
$strings['TheUserImageBlock'] = 'Din profilbild';
$strings['TheProfileBlock'] = 'Dina profilverktyg: <i>Inkorg</i>, <i>meddelandeskapare</i>, <i>väntande inbjudningar</i>, <i>profilredigering</i>.';
$strings['TheHomePageStep'] = 'Detta är den initiala hemsidan där du hittar portalens annonseringar, länkar och all information som administratörsteamet har konfigurerat.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Detta område visar de olika kurser (eller sessioner) som du är anmäld till. Om ingen kurs visas, gå till kurskatalogen (se meny) eller diskutera med din portaldministratör';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'Agendaverktyget låter dig se vilka evenemang som är planerade för de kommande dagarna, veckorna eller månaderna.';
$strings['AgendaTheActionBar'] = 'Du kan välja att visa evenemangen som en lista istället för i kalenderläge med hjälp av de medföljande åtgärdsikonerna';
$strings['AgendaTodayButton'] = 'Klicka på "idag"-knappen för att se endast dagens schema';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'Aktuell månad visas alltid tydligt i kalendervyn';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Du kan byta vy till daglig, veckovis eller månadsvis genom att klicka på en av dessa knappar';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Detta område låter dig kontrollera din framsteg om du är student, eller dina elevers framsteg om du är lärare';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Rapporterna på denna skärm är utökningsbara och kan ge dig värdefull insikt i ditt lärande eller undervisning';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'Det sociala området låter dig komma i kontakt med andra användare på plattformen';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'Menyerna ger dig tillgång till en serie skärmar som låter dig delta i privata meddelanden, chatt, intressegrupper, etc.';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'Instrumentpanelen låter dig få mycket specifik information i ett illustrerat och kondenserat format. Endast administratörer har tillgång till denna funktion för närvarande';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'För att aktivera instrumentpanelspaneler måste du först aktivera de möjliga panelerna i adminsektionen för plugins, sedan komma tillbaka hit och välja vilka paneler *du* vill se på din instrumentpanel';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'Adminpanelen låter dig hantera alla resurser i din Chamilo-portal';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'Användarblocket låter dig hantera allt som rör användare.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'Kursblocket ger dig tillgång till kurs skapande, redigering, etc. Andra block är avsedda för specifika användningar också.';


$strings['tour_home_featured_courses_title'] = 'Utvalda kurser';
$strings['tour_home_featured_courses_content'] = 'Denna sektion visar de utvalda kurserna som finns på din hemsida.';

$strings['tour_home_course_card_title'] = 'Kortkurs';
$strings['tour_home_course_card_content'] = 'Varje kort sammanfattar en kurs och ger dig snabb tillgång till dess huvudsakliga information.';

$strings['tour_home_course_title_title'] = 'Kurstitel';
$strings['tour_home_course_title_content'] = 'Kurstiteln hjälper dig att snabbt identifiera kursen och kan också öppna mer information beroende på plattformsinställningarna.';

$strings['tour_home_teachers_title'] = 'Lärare';
$strings['tour_home_teachers_content'] = 'Detta område visar lärarna eller användarna som är associerade med kursen.';

$strings['tour_home_rating_title'] = 'Betyg och feedback';
$strings['tour_home_rating_content'] = 'Här kan du granska kursens betyg och, när det är tillåtet, lämna din egen röst.';

$strings['tour_home_main_action_title'] = 'Huvudåtgärd för kurs';
$strings['tour_home_main_action_content'] = 'Använd denna knapp för att gå in i kursen, anmäla dig eller granska åtkomstbegränsningar beroende på kursens status.';

$strings['tour_home_show_more_title'] = 'Visa fler kurser';
$strings['tour_home_show_more_content'] = 'Använd denna knapp för att ladda fler kurser och fortsätta utforska katalogen från hemsidan.';

$strings['tour_my_courses_cards_title'] = 'Dina kurskort';
$strings['tour_my_courses_cards_content'] = 'Denna sida listar kurserna du är anmäld till. Varje kort ger dig snabb tillgång till kursen och dess aktuella status.';

$strings['tour_my_courses_image_title'] = 'Kursbild';
$strings['tour_my_courses_image_content'] = 'Kursbilden hjälper dig att snabbt identifiera kursen. I de flesta fall öppnar ett klick på den kursen.';

$strings['tour_my_courses_title_title'] = 'Kurs- och sessiontitel';
$strings['tour_my_courses_title_content'] = 'Här kan du se kursens titel och, när tillämpligt, sessionens namn som är kopplat till kursen.';

$strings['tour_my_courses_progress_title'] = 'Lärande framsteg';
$strings['tour_my_courses_progress_content'] = 'Denna framstegindikator visar hur mycket av kursen du har slutfört.';

$strings['tour_my_courses_notifications_title'] = 'Meddelanden om nytt innehåll';
$strings['tour_my_courses_notifications_content'] = 'Använd denna klockknapp för att kontrollera om kursen har nytt innehåll eller nyliga uppdateringar. När den är markerad hjälper den dig att snabbt upptäcka ändringar sedan ditt senaste besök.';

$strings['tour_my_courses_footer_title'] = 'Lärare och kursdetaljer';
$strings['tour_my_courses_footer_content'] = 'Sidfoten kan visa lärare, språk och annan användbar information relaterad till kursen.';

$strings['tour_my_courses_create_course_title'] = 'Skapa en kurs';
$strings['tour_my_courses_create_course_content'] = 'Om du har behörighet att skapa kurser, använd denna knapp för att öppna kursens skapandeformulär direkt från denna sida.';

$strings['tour_course_home_header_title'] = 'Kursrubrik';
$strings['tour_course_home_header_content'] = 'Denna rubrik visar kursens titel och, när tillämpligt, den aktiva sessionen. Den grupperar också huvudläraråtgärderna som finns på denna sida.';

$strings['tour_course_home_title_title'] = 'Kurstitel';
$strings['tour_course_home_title_content'] = 'Här kan du snabbt identifiera den aktuella kursen. Om kursen tillhör en session visas sessionstiteln bredvid.';

$strings['tour_course_home_teacher_tools_title'] = 'Lärarverktyg';
$strings['tour_course_home_teacher_tools_content'] = 'Beroende på dina behörigheter kan detta område inkludera växling till elevvy, redigering av introduktion, åtkomst till rapporter och ytterligare kursadministrationsåtgärder.';

$strings['tour_course_home_intro_title'] = 'Kursintroduktion';
$strings['tour_course_home_intro_content'] = 'Detta avsnitt visar kursens introduktion. Lärare kan använda det för att presentera mål, vägledning, länkar eller viktig information för eleverna.';

$strings['tour_course_home_tools_controls_title'] = 'VerktygsKontroller';
$strings['tour_course_home_tools_controls_content'] = 'Lärare kan använda dessa kontroller för att visa eller dölja alla verktyg på en gång eller aktivera sorteringsläge för att omorganisera kursverktygen.';

$strings['tour_course_home_tools_title'] = 'Kursverktyg';
$strings['tour_course_home_tools_content'] = 'Detta område innehåller huvudkursverktygen, såsom dokument, lärstigar, övningar, forum och andra resurser som finns i kursen.';

$strings['tour_course_home_tool_card_title'] = 'Verktygskort';
$strings['tour_course_home_tool_card_content'] = 'Varje verktygskort ger åtkomst till ett kursverktyg. Använd det för att snabbt gå in i det valda området av kursen.';

$strings['tour_course_home_tool_shortcut_title'] = 'Verktygskortkommando';
$strings['tour_course_home_tool_shortcut_content'] = 'Klicka på ikonområdet för att öppna det valda kursverktyget direkt.';

$strings['tour_course_home_tool_name_title'] = 'Verktygsnamn';
$strings['tour_course_home_tool_name_content'] = 'Titeln identifierar verktyget och fungerar också som en direkt åtkomstlänk.';

$strings['tour_course_home_tool_visibility_title'] = 'Verktygsynlighet';
$strings['tour_course_home_tool_visibility_content'] = 'Om du redigerar kursen låter denna knapp dig snabbt ändra verktygets synlighet för eleverna.';
$strings['tour_admin_overview_title'] = 'Administrationsinstrumentbräda';
$strings['tour_admin_overview_content'] = 'Denna sida centraliserar plattformens huvudadministrationsområden, grupperade efter hanteringsämne.';

$strings['tour_admin_user_management_title'] = 'Användarhantering';
$strings['tour_admin_user_management_content'] = 'Från detta block kan du hantera registrerade användare, skapa konton, importera eller exportera användarlistor, redigera användare, anonymisera data och hantera klasser.';

$strings['tour_admin_course_management_title'] = 'Kursadministrering';
$strings['tour_admin_course_management_content'] = 'Detta block låter dig skapa och hantera kurser, importera eller exportera kurslistor, organisera kategorier, tilldela användare till kurser och konfigurera kursrelaterade fält och verktyg.';

$strings['tour_admin_sessions_management_title'] = 'Sessionhantering';
$strings['tour_admin_sessions_management_content'] = 'Här kan du hantera utbildningsessioner, sessionkategorier, importer och exporter, HR-direktörer, karriärer, befordringar och sessionrelaterade fält.';

$strings['tour_admin_platform_management_title'] = 'Plattformshantering';
$strings['tour_admin_platform_management_content'] = 'Använd detta block för att konfigurera plattformen globalt, justera inställningar, hantera annonseringar, språk och andra centrala administrationsalternativ.';

$strings['tour_admin_tracking_title'] = 'Spårning';
$strings['tour_admin_tracking_content'] = 'Detta område ger åtkomst till rapporter, globala statistik, lärandeanalys och annan spårningsdata över hela plattformen.';

$strings['tour_admin_assessments_title'] = 'Bedömningar';
$strings['tour_admin_assessments_content'] = 'Detta block ger åtkomst till administrationsfunktioner relaterade till bedömningar som finns på plattformen.';
$strings['tour_admin_skills_title'] = 'Färdigheter';
$strings['tour_admin_skills_content'] = 'Detta block låter dig hantera användarfärdigheter, färdighetsimporter, rankningar, nivåer och bedömningar relaterade till färdigheter.';

$strings['tour_admin_system_title'] = 'System';
$strings['tour_admin_system_content'] = 'Här kan du komma åt verktyg för server- och plattformsunderhåll, såsom systemstatus, rensning av temporära filer, datafyllare, e-posttester och tekniska verktyg.';

$strings['tour_admin_rooms_title'] = 'Rum';
$strings['tour_admin_rooms_content'] = 'Detta block ger åtkomst till rumshanteringsfunktioner, inklusive filialer, rum och sökning efter rums tillgänglighet.';

$strings['tour_admin_security_title'] = 'Säkerhet';
$strings['tour_admin_security_content'] = 'Använd detta område för att granska inloggningsförsök, säkerhetsrelaterade rapporter och ytterligare säkerhetsverktyg som finns på plattformen.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Detta block ger officiella Chamilo-referenser, användarhandböcker, forum, installationsresurser och länkar till tjänsteleverantörer och projektinformation.';

$strings['tour_admin_health_check_title'] = 'Hälsokontroll';
$strings['tour_admin_health_check_content'] = 'Detta område hjälper dig att granska plattformens tekniska hälsa genom att lista miljöKontroller, skrivbara sökvägar och viktiga installationsvarningar.';

$strings['tour_admin_version_check_title'] = 'Versionskontroll';
$strings['tour_admin_version_check_content'] = 'Använd detta block för att registrera din portal och aktivera versionskontrollfunktioner och offentliga plattformslistanteringsalternativ.';

$strings['tour_admin_professional_support_title'] = 'Professionellt stöd';
$strings['tour_admin_professional_support_content'] = 'Detta block förklarar hur du kontaktar officiella Chamilo-leverantörer för konsultation, värd, utbildning och anpassad utvecklingsstöd.';

$strings['tour_admin_news_title'] = 'Nyheter från Chamilo';
$strings['tour_admin_news_content'] = 'Denna sektion visar senaste nyheter och meddelanden från Chamilo-projektet.';

$strings['tour_home_topbar_logo_title'] = 'Plattformslogotyp';
$strings['tour_home_topbar_logo_content'] = 'Den här logotypen tar dig tillbaka till plattformens startsida.';
$strings['tour_home_topbar_actions_title'] = 'Snabbåtgärder';
$strings['tour_home_topbar_actions_content'] = 'Här hittar du genvägsikoner som kursskapande, guidad hjälp, ärenden och meddelanden beroende på din roll.';
$strings['tour_home_menu_button_title'] = 'Menyknapp';
$strings['tour_home_menu_button_content'] = 'Använd den här knappen för att snabbt öppna eller stänga sidomenyn.';
$strings['tour_home_sidebar_title'] = 'Huvudmeny';
$strings['tour_home_sidebar_content'] = 'Den här sidomenyn ger åtkomst till plattformens huvudsektioner beroende på dina behörigheter.';
$strings['tour_home_user_area_title'] = 'Användarområde';
$strings['tour_home_user_area_content'] = 'Här kan du komma åt din profil, personliga alternativ och logga ut.';
