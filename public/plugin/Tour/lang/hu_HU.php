<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Bemutató';
$strings['plugin_comment'] = 'Ez a bővítmény bemutatja az embereknek, hogyan használják a Chamilo LMS-t. Aktiválnia kell egy régiót (pl. „header-right”), hogy megjelenjen a gomb, amellyel elindítható a bemutató.';

/* Strings for settings */
$strings['show_tour'] = 'Bemutató megjelenítése';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'A súgóblokkokat megjelenítő szükséges konfiguráció JSON formátumban a <strong>plugin/tour/config/tour.json</strong> fájlban található. <br> További információkért lásd a README fájlt.';

$strings['theme'] = 'Téma';
$strings['theme_help'] = 'Válassza a <i>nassim</i>, <i>nazanin</i>, <i>royal</i> témát. Üresen hagyva az alapértelmezett téma lesz használva.';

/* Strings for plugin UI */
$strings['Skip'] = 'Kihagyás';
$strings['Next'] = 'Következő';
$strings['Prev'] = 'Előző';
$strings['Done'] = 'Kész';
$strings['StartButtonText'] = 'Bemutató indítása';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Üdvözöljük a <b>Chamilo LMS 1.9.x</b>-ben';
$strings['TheNavbarStep'] = 'Menüsáv a portál fő szakaszaihoz vezető hivatkozásokkal';
$strings['TheRightPanelStep'] = 'Oldalsáv panel';
$strings['TheUserImageBlock'] = 'Profilfotója';
$strings['TheProfileBlock'] = 'Profil eszközei: <i>Bejövő üzenetek</i>, <i>üzenetszerkesztő</i>, <i>függőben lévő meghívások</i>, <i>profil szerkesztése</i>.';
$strings['TheHomePageStep'] = 'Ez a kezdőlap, ahol megtalálja a portál bejelentéseit, hivatkozásokat és az adminisztrációs csapat által konfigurált információkat.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Ez a terület mutatja azokat a kurzusokat (vagy foglalkozásokat), amelyekre feliratkozott. Ha nem jelenik meg kurzus, keresse fel a kurzuskatalógust (lásd menü) vagy beszélje meg a portál adminisztrátorával';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'A naptár eszköz lehetővé teszi, hogy megtekintse a közelgő napokra, hetekre vagy hónapokra ütemezett eseményeket.';
$strings['AgendaTheActionBar'] = 'Dönthet úgy, hogy listaként jeleníti meg az eseményeket a naptárnézet helyett, a megadott műveleti ikonok használatával';
$strings['AgendaTodayButton'] = 'Kattintson a „ma” gombra a mai ütemterv megtekintéséhez';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'A naptárnézetben mindig kiemelten látható a jelenlegi hónap';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Ezekre a gombokra kattintva átválthat napi, heti vagy havi nézetre';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Ez a terület lehetővé teszi, hogy ellenőrizze haladását, ha hallgató, vagy diákjai haladását, ha tanár';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Ezen a képernyőn látható jelentések bővíthetők, és nagyon értékes betekintést nyújthatnak tanulási vagy tanítási tevékenységébe';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'A közösségi terület lehetővé teszi, hogy kapcsolatba lépjen a platformon lévő többi felhasználóval';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'A menü一系列 képernyőhöz biztosít hozzáférést, amelyek lehetővé teszik a részvételt privát üzenetküldésben, csevegésben, érdeklődési csoportokban stb.';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'A vezérlőpult nagyon specifikus információkat jelenít meg illusztrált és tömörített formátumban. Jelenleg csak az adminisztrátorok férnek hozzá ehhez a funkcióhoz';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'A vezérlőpult panelek engedélyezéséhez először aktiválja a lehetséges paneleket az adminisztrátori bővítmények szakaszában, majd térjen vissza ide, és válassza ki, hogy *melyik paneleket* szeretné látni a vezérlőpultján';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'Az adminisztrátori panel lehetővé teszi a Chamilo portál összes erőforrásának kezelését';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'A felhasználók blokk lehetővé teszi a felhasználókkal kapcsolatos összes dolog kezelését.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'A kurzusok blokk hozzáférést biztosít a kurzus létrehozásához, szerkesztéséhez stb. A többi blokk szintén specifikus használatokra van dedikálva.';


$strings['tour_home_featured_courses_title'] = 'Kiemelt kurzusok';
$strings['tour_home_featured_courses_content'] = 'Ez a szakasz mutatja a kezdőlapon elérhető kiemelt kurzusokat.';

$strings['tour_home_course_card_title'] = 'Kurzuskártya';
$strings['tour_home_course_card_content'] = 'Minden kártya összefoglalja egy kurzust, és gyors hozzáférést biztosít annak fő információihoz.';

$strings['tour_home_course_title_title'] = 'Kurzus címe';
$strings['tour_home_course_title_content'] = 'A kurzus címe segít gyorsan azonosítani a kurzust, és a platform beállításaitól függően további információkat is megnyithat.';

$strings['tour_home_teachers_title'] = 'Tanárok';
$strings['tour_home_teachers_content'] = 'Ez a terület mutatja a kurzushoz tartozó tanárokat vagy felhasználókat.';

$strings['tour_home_rating_title'] = 'Értékelés és visszajelzés';
$strings['tour_home_rating_content'] = 'Itt megtekintheti a kurzus értékelését, és ha megengedett, leadhatja saját szavazatát.';

$strings['tour_home_main_action_title'] = 'Fő kurzusművelet';
$strings['tour_home_main_action_content'] = 'Használja ezt a gombot a kurzusba lépéshez, feliratkozáshoz vagy a hozzáférési korlátozások megtekintéséhez a kurzus állapotától függően.';

$strings['tour_home_show_more_title'] = 'További kurzusok megjelenítése';
$strings['tour_home_show_more_content'] = 'Használja ezt a gombot további kurzusok betöltéséhez és a katalógus további felfedezéséhez a kezdőlapról.';

$strings['tour_my_courses_cards_title'] = 'Kurzuskártyái';
$strings['tour_my_courses_cards_content'] = 'Ez az oldal felsorolja a feliratkozott kurzusait. Minden kártya gyors hozzáférést biztosít a kurzushoz és annak aktuális állapotához.';

$strings['tour_my_courses_image_title'] = 'Kurzusképlet';
$strings['tour_my_courses_image_content'] = 'A kurzusképlet segít gyorsan azonosítani a kurzust. A legtöbb esetben kattintással megnyílik a kurzus.';

$strings['tour_my_courses_title_title'] = 'Kurzus és foglalkozás címe';
$strings['tour_my_courses_title_content'] = 'Itt láthatja a kurzus címét és, ha van, a kurzushoz tartozó foglalkozás nevét.';

$strings['tour_my_courses_progress_title'] = 'Tanulási előrehaladás';
$strings['tour_my_courses_progress_content'] = 'Ez a előrehaladási sáv azt mutatja, hogy a kurzus hány százalékát teljesítette.';

$strings['tour_my_courses_notifications_title'] = 'Új tartalom értesítések';
$strings['tour_my_courses_notifications_content'] = 'Használja ezt a csengő gombot, hogy ellenőrizze, van-e új tartalom vagy frissítés a kurzusban. Ha kiemelve jelenik meg, gyorsan észreveszi a legutóbbi hozzáférése óta bekövetkezett változtatásokat.';

$strings['tour_my_courses_footer_title'] = 'Oktatók és kurzusteljesítmények';
$strings['tour_my_courses_footer_content'] = 'A láblécben megjelenhetnek az oktatók, a nyelv és más a kurzushoz kapcsolódó hasznos információk.';

$strings['tour_my_courses_create_course_title'] = 'Kurzus létrehozása';
$strings['tour_my_courses_create_course_content'] = 'Ha jogosult kurzusok létrehozására, használja ezt a gombot a kurzuskészítési űrlap megnyitásához közvetlenül erről az oldalról.';

$strings['tour_course_home_header_title'] = 'Kurzus fejléc';
$strings['tour_course_home_header_content'] = 'Ez a fejléc mutatja a kurzus címét és, ha van, az aktív foglalkozást. Emellett csoportosítja az oldalon elérhető fő oktatói műveleteket.';

$strings['tour_course_home_title_title'] = 'Kurzus címe';
$strings['tour_course_home_title_content'] = 'Itt gyorsan azonosíthatja az aktuális kurzust. Ha a kurzus foglalkozáshoz tartozik, mellette megjelenik a foglalkozás címe.';

$strings['tour_course_home_teacher_tools_title'] = 'Oktatói eszközök';
$strings['tour_course_home_teacher_tools_content'] = 'Jogosultságaitól függően ez a terület tartalmazhatja a hallgatói nézet váltót, a bevezető szerkesztését, a jelentések elérését és további kurzusszintű kezelési műveleteket.';

$strings['tour_course_home_intro_title'] = 'Kurzus bevezetője';
$strings['tour_course_home_intro_content'] = 'Ez a szakasz a kurzus bevezetőjét jeleníti meg. Az oktatók használhatják célok, útmutatás, hivatkozások vagy kulcsfontosságú információk bemutatására a tanulók számára.';

$strings['tour_course_home_tools_controls_title'] = 'Eszközvezérlők';
$strings['tour_course_home_tools_controls_content'] = 'Az oktatók ezekkel a vezérlőkkel egyszerre megjeleníthetik vagy elrejthetik az összes eszközt, vagy bekapcsolhatják a rendezési módot a kurzus eszközeinek átrendezéséhez.';

$strings['tour_course_home_tools_title'] = 'Kurzus eszközök';
$strings['tour_course_home_tools_content'] = 'Ez a terület a fő kurzus eszközeit tartalmazza, például dokumentumokat, tanulási útvonalakat, feladatokat, fórumokat és más a kurzusban elérhető erőforrásokat.';

$strings['tour_course_home_tool_card_title'] = 'Eszköz kártya';
$strings['tour_course_home_tool_card_content'] = 'Minden eszközkártya hozzáférést biztosít egy kurzus eszközhöz. Használja a kiválasztott kurzus területének gyors eléréséhez.';

$strings['tour_course_home_tool_shortcut_title'] = 'Eszköz gyorsbillentyű';
$strings['tour_course_home_tool_shortcut_content'] = 'Kattintson az ikon területére a kiválasztott kurzus eszköz közvetlen megnyitásához.';

$strings['tour_course_home_tool_name_title'] = 'Eszköz neve';
$strings['tour_course_home_tool_name_content'] = 'A cím azonosítja az eszközt, és közvetlen hozzáférési hivatkozásként is működik.';

$strings['tour_course_home_tool_visibility_title'] = 'Eszköz láthatósága';
$strings['tour_course_home_tool_visibility_content'] = 'Ha szerkeszti a kurzust, ezzel a gombbal gyorsan megváltoztathatja az eszköz láthatóságát a tanulók számára.';
$strings['tour_admin_overview_title'] = 'Adminisztrációs vezérlőpult';
$strings['tour_admin_overview_content'] = 'Ez az oldal a platform fő adminisztrációs területeit összegyűjti, kezelési témák szerint csoportosítva.';

$strings['tour_admin_user_management_title'] = 'Felhasználókezelés';
$strings['tour_admin_user_management_content'] = 'Ebből a blokkból kezelheti a regisztrált felhasználókat, létrehozhat fiókokat, importálhat vagy exportálhat felhasználói listákat, szerkeszthet felhasználókat, anonimizálhat adatokat és kezelheti a csoportokat.';

$strings['tour_admin_course_management_title'] = 'Kurzuskezelés';
$strings['tour_admin_course_management_content'] = 'Ez a blokk lehetővé teszi kurzusok létrehozását és kezelését, kurzuslisták importját vagy exportját, kategóriák szervezését, felhasználók hozzárendelését kurzusokhoz és a kurzushoz kapcsolódó mezők és eszközök konfigurálását.';

$strings['tour_admin_sessions_management_title'] = 'Foglalkozáskezelés';
$strings['tour_admin_sessions_management_content'] = 'Itt kezelheti a képzési foglalkozásokat, foglalkozáskategóriákat, importokat és exportokat, HR igazgatókat, pályákat, előrelépéseket és foglalkozáshoz kapcsolódó mezőket.';

$strings['tour_admin_platform_management_title'] = 'Platformkezelés';
$strings['tour_admin_platform_management_content'] = 'Használja ezt a blokkot a platform globális konfigurálására, beállítások módosítására, bejelentések kezelésére, nyelvek kezelésére és más központi adminisztrációs opciókra.';

$strings['tour_admin_tracking_title'] = 'Követés';
$strings['tour_admin_tracking_content'] = 'Ez a terület hozzáférést biztosít a jelentésekhez, globális statisztikákhoz, tanulási analitikához és más platformszintű követési adatokhoz.';

$strings['tour_admin_assessments_title'] = 'Értékelések';
$strings['tour_admin_assessments_content'] = 'Ez a blokk hozzáférést biztosít a platformon elérhető értékeléssel kapcsolatos adminisztrációs funkciókhoz.';
$strings['tour_admin_skills_title'] = 'Készségek';
$strings['tour_admin_skills_content'] = 'Ez a blokk lehetővé teszi felhasználói készségek kezelését, készségimportokat, rangsorokat, szinteket és készségekkel kapcsolatos értékeléseket.';

$strings['tour_admin_system_title'] = 'Rendszer';
$strings['tour_admin_system_content'] = 'Itt érheti el a szerver- és platformfenntartó eszközöket, például a rendszer állapotát, ideiglenes fájlok törlését, adatkitöltőt, e-mail teszteket és technikai segédprogramokat.';

$strings['tour_admin_rooms_title'] = 'Termek';
$strings['tour_admin_rooms_content'] = 'Ez a blokk hozzáférést biztosít a teremkezelési funkciókhoz, beleértve a fióktelepeket, termeket és a termek elérhetőségének keresését.';

$strings['tour_admin_security_title'] = 'Biztonság';
$strings['tour_admin_security_content'] = 'Használja ezt a területet a bejelentkezési kísérletek, biztonsági jelentések és a platformon elérhető további biztonsági eszközök áttekintéséhez.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Ez a blokk hivatalos Chamilo hivatkozásokat, felhasználói útmutatókat, fórumokat, telepítési erőforrásokat és szolgáltatókra, valamint projektinformációkra mutató linkeket biztosít.';

$strings['tour_admin_health_check_title'] = 'Rendszerellenőrzés';
$strings['tour_admin_health_check_content'] = 'Ez a terület segít a platform technikai állapotának áttekintésében a környezeti ellenőrzések, írható útvonalak és fontos telepítési figyelmeztetések listázásával.';

$strings['tour_admin_version_check_title'] = 'Verzióellenőrzés';
$strings['tour_admin_version_check_content'] = 'Használja ezt a blokkot a portál regisztrálásához, a verzióellenőrzési funkciók és a nyilvános platformlistázási opciók engedélyezéséhez.';

$strings['tour_admin_professional_support_title'] = 'Szakmai támogatás';
$strings['tour_admin_professional_support_content'] = 'Ez a blokk elmagyarázza, hogyan vegye fel a kapcsolatot a hivatalos Chamilo szolgáltatókkal tanácsadás, tárhely, képzés és egyedi fejlesztési támogatás érdekében.';

$strings['tour_admin_news_title'] = 'Hírek a Chamilo-tól';
$strings['tour_admin_news_content'] = 'Ez a szakasz a Chamilo projekt legfrissebb híreit és bejelentéseit jeleníti meg.';

$strings['tour_home_topbar_logo_title'] = 'A platform logója';
$strings['tour_home_topbar_logo_content'] = 'Ez a logó visszavisz a platform kezdőlapjára.';
$strings['tour_home_topbar_actions_title'] = 'Gyors műveletek';
$strings['tour_home_topbar_actions_content'] = 'Itt olyan gyorsikonokat talál, mint a kurzuslétrehozás, irányított segítség, jegyek és üzenetek, a szerepkörétől függően.';
$strings['tour_home_menu_button_title'] = 'Menü gomb';
$strings['tour_home_menu_button_content'] = 'Ezzel a gombbal gyorsan megnyithatja vagy bezárhatja az oldalsó menüt.';
$strings['tour_home_sidebar_title'] = 'Főmenü';
$strings['tour_home_sidebar_content'] = 'Ez az oldalsó menü hozzáférést ad a platform fő részeihez a jogosultságai szerint.';
$strings['tour_home_user_area_title'] = 'Felhasználói terület';
$strings['tour_home_user_area_content'] = 'Itt érheti el a profilját, személyes beállításait és kijelentkezhet.';
