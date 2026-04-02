<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Prohlídka';
$strings['plugin_comment'] = 'Tento plugin ukazuje lidem, jak používat váš Chamilo LMS. Musíte aktivovat jednu oblast (např. „header-right“), aby se zobrazilo tlačítko spouštějící prohlídku.';

/* Strings for settings */
$strings['show_tour'] = 'Zobrazit prohlídku';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'Potřebná konfigurace pro zobrazení bloků nápovědy ve formátu JSON se nachází v souboru <strong>plugin/tour/config/tour.json</strong>. <br> Další informace viz soubor README.';

$strings['theme'] = 'Téma';
$strings['theme_help'] = 'Vyberte <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Prázdné pro výchozí téma.';

/* Strings for plugin UI */
$strings['Skip'] = 'Přeskočit';
$strings['Next'] = 'Další';
$strings['Prev'] = 'Předchozí';
$strings['Done'] = 'Dokončeno';
$strings['StartButtonText'] = 'Spustit prohlídku';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Vítejte v <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Panel nabídky s odkazy na hlavní sekce portálu';
$strings['TheRightPanelStep'] = 'Boční panel';
$strings['TheUserImageBlock'] = 'Vaše profilové foto';
$strings['TheProfileBlock'] = 'Nástroje vašeho profilu: <i>Doručená pošta</i>, <i>tvorba zpráv</i>, <i>čekající pozvánky</i>, <i>úprava profilu</i>.';
$strings['TheHomePageStep'] = 'Toto je úvodní domovská stránka, kde najdete oznámení portálu, odkazy a jakékoli informace, které nastavil tým administrátorů.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Tato oblast zobrazuje různé kurzy (nebo relace), ke kterým jste přihlášeni. Pokud se žádný kurz nezobrazí, přejděte do katalogu kurzů (viz nabídka) nebo se obraťte na administrátora portálu.';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'Nástroj kalendář vám umožňuje zobrazit události naplánované na příští dny, týdny nebo měsíce.';
$strings['AgendaTheActionBar'] = 'Můžete se rozhodnout zobrazit události jako seznam místo zobrazení v kalendáři pomocí poskytnutých ikon akcí.';
$strings['AgendaTodayButton'] = 'Kliknutím na tlačítko „dnes“ zobrazíte pouze dnešní plán.';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'Aktuální měsíc je vždy zvýrazněn ve zobrazení kalendáře.';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Můžete přepnout zobrazení na denní, týdenní nebo měsíční kliknutím na jedno z těchto tlačítek.';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Tato oblast vám umožňuje zkontrolovat váš pokrok, pokud jste student, nebo pokrok vašich studentů, pokud jste učitel.';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Zprávy poskytnuté na této obrazovce jsou rozšiřitelné a mohou vám poskytnout velmi cenné poznatky o vašem učení nebo výuce.';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'Sociální oblast vám umožňuje kontaktovat ostatní uživatele na platformě.';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'Nabídka vám poskytuje přístup k řadě obrazovek umožňujících účast na soukromých zprávách, chatu, zájmových skupinách atd.';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'Přehled umožňuje získat velmi specifické informace v ilustrovaném a kondenzovaném formátu. V současnosti mají k této funkci přístup pouze administrátoři.';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'Pro aktivaci panelů přehledu musíte nejprve aktivovat možné panely v administrační sekci pro pluginy, poté se vraťte sem a vyberte, které panely *vy* chcete na svém přehledu vidět.';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'Administrátorský panel vám umožňuje spravovat všechny zdroje ve vašem portálu Chamilo.';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'Blok uživatelů vám umožňuje spravovat vše související s uživateli.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'Blok kurzů vám poskytuje přístup k vytváření, úpravě kurzů atd. Další bloky jsou věnovány specifickým použitím.';


$strings['tour_home_featured_courses_title'] = 'Doporučené kurzy';
$strings['tour_home_featured_courses_content'] = 'Tato sekce zobrazuje doporučené kurzy dostupné na vaší domovské stránce.';

$strings['tour_home_course_card_title'] = 'Karta kurzu';
$strings['tour_home_course_card_content'] = 'Každá karta shrnuje jeden kurz a poskytuje rychlý přístup k jeho hlavním informacím.';

$strings['tour_home_course_title_title'] = 'Název kurzu';
$strings['tour_home_course_title_content'] = 'Název kurzu vám pomůže rychle kurz identifikovat a může také otevřít další informace v závislosti na nastavení platformy.';

$strings['tour_home_teachers_title'] = 'Učitelé';
$strings['tour_home_teachers_content'] = 'Tato oblast zobrazuje učitele nebo uživatele spojené s kurzem.';

$strings['tour_home_rating_title'] = 'Hodnocení a zpětná vazba';
$strings['tour_home_rating_content'] = 'Zde můžete zkontrolovat hodnocení kurzu a, pokud je to povoleno, odeslat svůj vlastní hlas.';

$strings['tour_home_main_action_title'] = 'Hlavní akce kurzu';
$strings['tour_home_main_action_content'] = 'Použijte toto tlačítko k vstupu do kurzu, přihlášení nebo zobrazení omezení přístupu v závislosti na stavu kurzu.';

$strings['tour_home_show_more_title'] = 'Zobrazit více kurzů';
$strings['tour_home_show_more_content'] = 'Použijte toto tlačítko k načtení dalších kurzů a pokračování v prozkoumávání katalogu z domovské stránky.';

$strings['tour_my_courses_cards_title'] = 'Vaše karty kurzů';
$strings['tour_my_courses_cards_content'] = 'Tato stránka uvádí kurzy, ke kterým jste přihlášeni. Každá karta poskytuje rychlý přístup k kurzu a jeho aktuálnímu stavu.';

$strings['tour_my_courses_image_title'] = 'Obrázek kurzu';
$strings['tour_my_courses_image_content'] = 'Obrázek kurzu vám pomůže kurz rychle identifikovat. Ve většině případů otevře kliknutím kurz.';

$strings['tour_my_courses_title_title'] = 'Název kurzu a relace';
$strings['tour_my_courses_title_content'] = 'Zde vidíte název kurzu a, pokud je relevantní, název relace spojené s tímto kurzem.';

$strings['tour_my_courses_progress_title'] = 'Pokrok ve výuce';
$strings['tour_my_courses_progress_content'] = 'Tento pruh pokroku ukazuje, kolik kurzu jste dokončili.';

$strings['tour_my_courses_notifications_title'] = 'Oznámení o novém obsahu';
$strings['tour_my_courses_notifications_content'] = 'Použijte toto tlačítko zvona k ověření, zda kurz obsahuje nový obsah nebo nedávné aktualizace. Pokud je zvýrazněné, pomůže vám rychle najít změny od vaší poslední návštěvy.';

$strings['tour_my_courses_footer_title'] = 'Vyučující a podrobnosti kurzu';
$strings['tour_my_courses_footer_content'] = 'Patička může zobrazovat vyučující, jazyk a další užitečné informace týkající se kurzu.';

$strings['tour_my_courses_create_course_title'] = 'Vytvořit kurz';
$strings['tour_my_courses_create_course_content'] = 'Pokud máte oprávnění vytvářet kurzy, použijte toto tlačítko k otevření formuláře pro vytvoření kurzu přímo z této stránky.';

$strings['tour_course_home_header_title'] = 'Záhlaví kurzu';
$strings['tour_course_home_header_content'] = 'Toto záhlaví zobrazuje název kurzu a, pokud je relevantní, aktivní relaci. Také shromažďuje hlavní akce vyučujícího dostupné na této stránce.';

$strings['tour_course_home_title_title'] = 'Název kurzu';
$strings['tour_course_home_title_content'] = 'Zde můžete rychle identifikovat aktuální kurz. Pokud kurz patří do relace, název relace je zobrazen vedle něj.';

$strings['tour_course_home_teacher_tools_title'] = 'Nástroje vyučujícího';
$strings['tour_course_home_teacher_tools_content'] = 'V závislosti na vašich oprávněních může tato oblast obsahovat přepínač pohledu studenta, úpravu úvodu, přístup k hlášením a další akce pro správu kurzu.';

$strings['tour_course_home_intro_title'] = 'Úvod do kurzu';
$strings['tour_course_home_intro_content'] = 'Tato sekce zobrazuje úvod do kurzu. Vyučující ho mohou použít k prezentaci cílů, pokynů, odkazů nebo klíčových informací pro studenty.';

$strings['tour_course_home_tools_controls_title'] = 'Ovládací prvky nástrojů';
$strings['tour_course_home_tools_controls_content'] = 'Vyučující mohou tyto ovládací prvky použít k zobrazení nebo skrytí všech nástrojů najednou nebo k zapnutí režimu třídění pro přeuspořádání nástrojů kurzu.';

$strings['tour_course_home_tools_title'] = 'Nástroje kurzu';
$strings['tour_course_home_tools_content'] = 'Tato oblast obsahuje hlavní nástroje kurzu, jako jsou dokumenty, výukové cesty, cvičení, fóra a další zdroje dostupné v kurzu.';

$strings['tour_course_home_tool_card_title'] = 'Karta nástroje';
$strings['tour_course_home_tool_card_content'] = 'Každá karta nástroje poskytuje přístup k jednomu nástroji kurzu. Použijte ji k rychlému vstupu do vybrané oblasti kurzu.';

$strings['tour_course_home_tool_shortcut_title'] = 'Zkratka nástroje';
$strings['tour_course_home_tool_shortcut_content'] = 'Klikněte na oblast ikony pro přímé otevření vybraného nástroje kurzu.';

$strings['tour_course_home_tool_name_title'] = 'Název nástroje';
$strings['tour_course_home_tool_name_content'] = 'Název identifikuje nástroj a zároveň slouží jako přímý odkaz.';

$strings['tour_course_home_tool_visibility_title'] = 'Viditelnost nástroje';
$strings['tour_course_home_tool_visibility_content'] = 'Pokud kurzem upravujete, toto tlačítko vám umožní rychle změnit viditelnost nástroje pro studenty.';
$strings['tour_admin_overview_title'] = 'Správní nástěnka';
$strings['tour_admin_overview_content'] = 'Tato stránka centralizuje hlavní správní oblasti platformy, seskupené podle tématu správy.';

$strings['tour_admin_user_management_title'] = 'Správa uživatelů';
$strings['tour_admin_user_management_content'] = 'Z tohoto bloku můžete spravovat registrované uživatele, vytvářet účty, importovat nebo exportovat seznamy uživatelů, upravovat uživatele, anonymizovat data a spravovat třídy.';

$strings['tour_admin_course_management_title'] = 'Správa kurzů';
$strings['tour_admin_course_management_content'] = 'Tento blok vám umožňuje vytvářet a spravovat kurzy, importovat nebo exportovat seznamy kurzů, organizovat kategorie, přiřazovat uživatele do kurzů a konfigurovat pole a nástroje související s kurzem.';

$strings['tour_admin_sessions_management_title'] = 'Správa relací';
$strings['tour_admin_sessions_management_content'] = 'Zde můžete spravovat školicí relace, kategorie relací, importy a exporty, HR ředitele, kariérní dráhy, povýšení a pole související s relacemi.';

$strings['tour_admin_platform_management_title'] = 'Správa platformy';
$strings['tour_admin_platform_management_content'] = 'Použijte tento blok k globální konfiguraci platformy, úpravě nastavení, správě oznámení, jazyků a dalších centrálních správcovských možností.';

$strings['tour_admin_tracking_title'] = 'Sledování';
$strings['tour_admin_tracking_content'] = 'Tato oblast poskytuje přístup k hlášením, globálním statistikám, výukové analytice a dalším sledovacím datům napříč platformou.';

$strings['tour_admin_assessments_title'] = 'Hodnocení';
$strings['tour_admin_assessments_content'] = 'Tento blok poskytuje přístup k funkcím správy hodnocení dostupným na platformě.';
$strings['tour_admin_skills_title'] = 'Dovednosti';
$strings['tour_admin_skills_content'] = 'Tento blok vám umožňuje spravovat dovednosti uživatelů, import dovedností, žebříčky, úrovně a hodnocení související s dovednostmi.';

$strings['tour_admin_system_title'] = 'Systém';
$strings['tour_admin_system_content'] = 'Zde máte přístup k nástrojům pro údržbu serveru a platformy, jako je stav systému, čištění dočasných souborů, plnič dat, testy e-mailů a technické nástroje.';

$strings['tour_admin_rooms_title'] = 'Místnosti';
$strings['tour_admin_rooms_content'] = 'Tento blok poskytuje přístup k funkcím správy místností, včetně poboček, místností a vyhledávání dostupnosti místností.';

$strings['tour_admin_security_title'] = 'Bezpečnost';
$strings['tour_admin_security_content'] = 'Použijte tuto oblast k prohlédnutí pokusů o přihlášení, zpráv souvisejících s bezpečností a dalších bezpečnostních nástrojů dostupných na platformě.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Tento blok poskytuje oficiální reference Chamilo, průvodce uživatele, fóra, instalační zdroje a odkazy na poskytovatele služeb a informace o projektu.';

$strings['tour_admin_health_check_title'] = 'Kontrola stavu';
$strings['tour_admin_health_check_content'] = 'Tato oblast vám pomůže zkontrolovat technický stav platformy výčtem kontrol prostředí, zápisovatelných cest a důležitých varování při instalaci.';

$strings['tour_admin_version_check_title'] = 'Kontrola verze';
$strings['tour_admin_version_check_content'] = 'Použijte tento blok k registraci vašeho portálu a povolení funkcí kontroly verze a možností veřejného výpisu platformy.';

$strings['tour_admin_professional_support_title'] = 'Profesionální podpora';
$strings['tour_admin_professional_support_content'] = 'Tento blok vysvětluje, jak kontaktovat oficiální poskytovatele Chamilo pro konzultace, hosting, školení a podporu při vlastním vývoji.';

$strings['tour_admin_news_title'] = 'Novinky z Chamilo';
$strings['tour_admin_news_content'] = 'Tato sekce zobrazuje nejnovější novinky a oznámení z projektu Chamilo.';
