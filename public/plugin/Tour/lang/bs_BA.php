<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Tura';
$strings['plugin_comment'] = 'Ovaj dodatak pokazuje ljudima kako koristiti vaš Chamilo LMS. Morate aktivirati jednu regiju (npr. "header-right") da biste prikazali gumb koji omogućava pokretanje ture.';

/* Strings for settings */
$strings['show_tour'] = 'Prikaži turu';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'Potrebna konfiguracija za prikaz blokova pomoći, u JSON formatu, nalazi se u datoteci <strong>plugin/tour/config/tour.json</strong>. <br> Pogledajte README datoteku za više informacija.';

$strings['theme'] = 'Tema';
$strings['theme_help'] = 'Odaberite <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Prazno za korištenje podrazumijevane teme.';

/* Strings for plugin UI */
$strings['Skip'] = 'Preskoči';
$strings['Next'] = 'Sljedeće';
$strings['Prev'] = 'Prethodno';
$strings['Done'] = 'Gotovo';
$strings['StartButtonText'] = 'Pokreni turu';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Dobrodošli u <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Traka s izbornikom sa vezama do glavnih odjeljaka portala';
$strings['TheRightPanelStep'] = 'Bočni panel';
$strings['TheUserImageBlock'] = 'Vaša profilna fotografija';
$strings['TheProfileBlock'] = 'Vaši alati profila: <i>Poruke</i>, <i>sastavljač poruka</i>, <i>čekajući pozivi</i>, <i>uređivanje profila</i>.';
$strings['TheHomePageStep'] = 'Ovo je početna stranica gdje ćete pronaći najave portala, veze i sve informacije koje je konfigurirao tim administratora.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Ovo područje prikazuje različite kurseve (ili sesije) na koje ste pretplaćeni. Ako se ne prikazuje nijedan kurs, idite u katalog kurseva (pogledajte izbornik) ili raspravite to sa administratorom portala';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'Alat za kalendar omogućuje vam da vidite koje događaje su zakazani za nadolazeće dane, sedmice ili mjesece.';
$strings['AgendaTheActionBar'] = 'Možete odlučiti da prikažete događaje kao listu, umjesto u prikazu kalendara, koristeći ikone radnji koje su pružene';
$strings['AgendaTodayButton'] = 'Kliknite gumb "danas" da vidite samo današnji raspored';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'Trenutni mjesec je uvijek istaknut u prikazu kalendara';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Možete prebaciti prikaz na dnevni, sedmični ili mjesečni klikom na jedan od ovih gumba';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Ovo područje omogućuje vam da provjerite svoj napredak ako ste student, ili napredak vaših studenata ako ste nastavnik';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Izvještaji prikazani na ovom ekranu su proširivi i mogu vam pružiti vrlo vrijedne uvide u vaše učenje ili podučavanje';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'Društveno područje omogućuje vam kontakt sa drugim korisnicima na platformi';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'Izbornik vam daje pristup seriji ekrana koji omogućavaju sudjelovanje u privatnim porukama, četu, grupama interesa, itd.';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'Nadzorna ploča omogućuje vam vrlo specifične informacije u ilustriranom i sažetom formatu. Trenutno samo administratori imaju pristup ovoj značajci';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'Da biste omogućili panele nadzorne ploče, prvo ih morate aktivirati u administratorskom odjeljku za dodatke, zatim se vratite ovdje i odaberite koje panele *vi* želite vidjeti na svojoj nadzornoj ploči';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'Administratorski panel omogućuje vam upravljanje svim resursima u vašem Chamilo portalu';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'Blok korisnika omogućuje vam upravljanje svim stvarima vezanim za korisnike.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'Blok kurseva daje vam pristup kreiranju, uređivanju kurseva, itd. Ostali blokovi su posvećeni specifičnim upotrebama.';


$strings['tour_home_featured_courses_title'] = 'Istaknuti kursevi';
$strings['tour_home_featured_courses_content'] = 'Ova sekcija prikazuje istaknute kurseve dostupne na vašoj početnoj stranici.';

$strings['tour_home_course_card_title'] = 'Kartica kursa';
$strings['tour_home_course_card_content'] = 'Svaka kartica sažima jedan kurs i daje vam brzi pristup njegovim glavnim informacijama.';

$strings['tour_home_course_title_title'] = 'Naslov kursa';
$strings['tour_home_course_title_content'] = 'Naslov kursa pomaže vam da brzo identificirate kurs i može otvoriti više informacija ovisno o postavkama platforme.';

$strings['tour_home_teachers_title'] = 'Nastavnici';
$strings['tour_home_teachers_content'] = 'Ovo područje prikazuje nastavnike ili korisnike povezane s kursom.';

$strings['tour_home_rating_title'] = 'Ocjena i povratne informacije';
$strings['tour_home_rating_content'] = 'Ovdje možete pregledati ocjenu kursa i, kada je dozvoljeno, poslati svoj glas.';

$strings['tour_home_main_action_title'] = 'Glavna radnja kursa';
$strings['tour_home_main_action_content'] = 'Koristite ovaj gumb da uđete u kurs, pretplatite se ili pregledate ograničenja pristupa ovisno o statusu kursa.';

$strings['tour_home_show_more_title'] = 'Prikaži više kurseva';
$strings['tour_home_show_more_content'] = 'Koristite ovaj gumb da učitate više kurseva i nastavite istraživati katalog sa početne stranice.';

$strings['tour_my_courses_cards_title'] = 'Vaše kartice kurseva';
$strings['tour_my_courses_cards_content'] = 'Ova stranica navodi kurseve na koje ste pretplaćeni. Svaka kartica daje vam brzi pristup kursu i njegovom trenutnom statusu.';

$strings['tour_my_courses_image_title'] = 'Slika kursa';
$strings['tour_my_courses_image_content'] = 'Slika kursa pomaže vam da brzo identificirate kurs. U većini slučajeva, klik na nju otvara kurs.';

$strings['tour_my_courses_title_title'] = 'Naziv kursa i sesije';
$strings['tour_my_courses_title_content'] = 'Ovdje možete vidjeti naziv kursa i, kada je primjenjivo, naziv sesije povezane s tim kursom.';

$strings['tour_my_courses_progress_title'] = 'Napredak u učenju';
$strings['tour_my_courses_progress_content'] = 'Ova traka napretka pokazuje koliko ste kursa završili.';

$strings['tour_my_courses_notifications_title'] = 'Obavijesti o novom sadržaju';
$strings['tour_my_courses_notifications_content'] = 'Koristite ovo zvono da provjerite ima li kurs novi sadržaj ili nedavne ažuriranja. Kada je istaknuto, pomaže vam brzo uočiti promjene od vašeg posljednjeg pristupa.';

$strings['tour_my_courses_footer_title'] = 'Nastavnici i detalji kursa';
$strings['tour_my_courses_footer_content'] = 'Donji dio može prikazati nastavnike, jezik i druge korisne informacije vezane za kurs.';

$strings['tour_my_courses_create_course_title'] = 'Kreiraj kurs';
$strings['tour_my_courses_create_course_content'] = 'Ako imate dopuštenje za kreiranje kurseva, koristite ovo dugme da otvorite obrazac za kreiranje kursa izravno s ove stranice.';

$strings['tour_course_home_header_title'] = 'Zaglavlje kursa';
$strings['tour_course_home_header_content'] = 'Ovo zaglavlje prikazuje naziv kursa i, kada je primjenjivo, aktivnu sesiju. Također grupira glavne radnje nastavnika dostupne na ovoj stranici.';

$strings['tour_course_home_title_title'] = 'Naziv kursa';
$strings['tour_course_home_title_content'] = 'Ovdje možete brzo prepoznati tekući kurs. Ako kurs pripada sesiji, naziv sesije se prikazuje pored njega.';

$strings['tour_course_home_teacher_tools_title'] = 'Alati nastavnika';
$strings['tour_course_home_teacher_tools_content'] = 'Ovisno o vašim ovlastima, ovaj dio može uključivati prebacivanje na prikaz studenta, uređivanje uvoda, pristup izvještajima i dodatne radnje upravljanja kursom.';

$strings['tour_course_home_intro_title'] = 'Uvod u kurs';
$strings['tour_course_home_intro_content'] = 'Ovaj dio prikazuje uvod u kurs. Nastavnici ga mogu koristiti za predstavljanje ciljeva, smjernica, linkova ili ključnih informacija za učenike.';

$strings['tour_course_home_tools_controls_title'] = 'Kontrole alata';
$strings['tour_course_home_tools_controls_content'] = 'Nastavnici mogu koristiti ove kontrole da prikažu ili sakriju sve alate odjednom ili omoguće način sortiranja za reorganizaciju alata kursa.';

$strings['tour_course_home_tools_title'] = 'Alati kursa';
$strings['tour_course_home_tools_content'] = 'Ovaj dio sadrži glavne alate kursa, kao što su dokumenti, putovi učenja, vježbe, forumi i drugi resursi dostupni u kursu.';

$strings['tour_course_home_tool_card_title'] = 'Kartica alata';
$strings['tour_course_home_tool_card_content'] = 'Svaka kartica alata daje pristup jednom alatu kursa. Koristite je da brzo uđete u odabrani dio kursa.';

$strings['tour_course_home_tool_shortcut_title'] = 'Prečica alata';
$strings['tour_course_home_tool_shortcut_content'] = 'Kliknite na područje ikone da izravno otvorite odabrani alat kursa.';

$strings['tour_course_home_tool_name_title'] = 'Naziv alata';
$strings['tour_course_home_tool_name_content'] = 'Naslov identificira alat i također služi kao direktna poveznica za pristup.';

$strings['tour_course_home_tool_visibility_title'] = 'Vidljivost alata';
$strings['tour_course_home_tool_visibility_content'] = 'Ako uređujete kurs, ovo dugme vam omogućuje brzu promjenu vidljivosti alata za učenike.';
$strings['tour_admin_overview_title'] = 'Administrativna kontrolna ploča';
$strings['tour_admin_overview_content'] = 'Ova stranica centralizira glavne administrativne oblasti platforme, grupirane po temama upravljanja.';

$strings['tour_admin_user_management_title'] = 'Upravljanje korisnicima';
$strings['tour_admin_user_management_content'] = 'Iz ovog bloka možete upravljati registrovanim korisnicima, kreirati račune, uvoziti ili izvoziti liste korisnika, uređivati korisnike, anonimizirati podatke i upravljati razredima.';

$strings['tour_admin_course_management_title'] = 'Upravljanje kursevima';
$strings['tour_admin_course_management_content'] = 'Ovaj blok vam omogućuje kreiranje i upravljanje kursevima, uvoz ili izvoz listi kurseva, organizaciju kategorija, dodjelu korisnika kursevima i konfiguraciju polja i alata vezanih za kurs.';

$strings['tour_admin_sessions_management_title'] = 'Upravljanje sesijama';
$strings['tour_admin_sessions_management_content'] = 'Ovdje možete upravljati sesijama obuke, kategorijama sesija, uvozima i izvozima, HR direktorima, karijerama, promaknućima i poljima vezanim za sesije.';

$strings['tour_admin_platform_management_title'] = 'Upravljanje platformom';
$strings['tour_admin_platform_management_content'] = 'Koristite ovaj blok za globalnu konfiguraciju platforme, podešavanje postavki, upravljanje najavama, jezicima i drugim centralnim administrativnim opcijama.';

$strings['tour_admin_tracking_title'] = 'Praćenje';
$strings['tour_admin_tracking_content'] = 'Ovaj dio daje pristup izvještajima, globalnim statistikama, analitikama učenja i drugim podacima praćenja na cijeloj platformi.';

$strings['tour_admin_assessments_title'] = 'Procjene';
$strings['tour_admin_assessments_content'] = 'Ovaj blok pruža pristup administrativnim značajkama vezanim za procjene dostupnim na platformi.';
$strings['tour_admin_skills_title'] = 'Vještine';
$strings['tour_admin_skills_content'] = 'Ovaj blok vam omogućuje upravljanje vještinama korisnika, uvoz vještina, rangiranja, razine i procjene vezane za vještine.';

$strings['tour_admin_system_title'] = 'Sistem';
$strings['tour_admin_system_content'] = 'Ovdje možete pristupiti alatima za održavanje servera i platforme, kao što su status sistema, čišćenje privremenih datoteka, popunjavanje podataka, testovi e-pošte i tehnički alati.';

$strings['tour_admin_rooms_title'] = 'Sobe';
$strings['tour_admin_rooms_content'] = 'Ovaj blok daje pristup značajkama upravljanja sobama, uključujući filijale, sobe i pretragu dostupnosti soba.';

$strings['tour_admin_security_title'] = 'Sigurnost';
$strings['tour_admin_security_content'] = 'Koristite ovaj dio za pregled pokušaja prijave, izvještaja vezanih za sigurnost i dodatnih alata za sigurnost dostupnih na platformi.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Ovaj blok pruža službene Chamilo reference, korisnička uputstva, forume, instalacijske resurse i linkove do pružatelja usluga i informacija o projektu.';

$strings['tour_admin_health_check_title'] = 'Provjera zdravlja';
$strings['tour_admin_health_check_content'] = 'Ovaj dio pomaže vam pregledati tehničko zdravlje platforme navođenjem provjera okruženja, upisivih putanja i važnih upozorenja o instalaciji.';

$strings['tour_admin_version_check_title'] = 'Provjera verzije';
$strings['tour_admin_version_check_content'] = 'Koristite ovaj blok za registraciju vašeg portala i omogućavanje značajki provjere verzije i opcija javnog popisa platforme.';

$strings['tour_admin_professional_support_title'] = 'Profesionalna podrška';
$strings['tour_admin_professional_support_content'] = 'Ovaj blok objašnjava kako kontaktirati službene Chamilo pružatelje za konsultacije, hosting, obuku i podršku za prilagođeni razvoj.';

$strings['tour_admin_news_title'] = 'Vijesti iz Chamila';
$strings['tour_admin_news_content'] = 'Ova sekcija prikazuje nedavne vijesti i najave iz Chamilo projekta.';

$strings['tour_home_topbar_logo_title'] = 'Logo platforme';
$strings['tour_home_topbar_logo_content'] = 'Ovaj logo vas vraća na početnu stranicu platforme.';
$strings['tour_home_topbar_actions_title'] = 'Brze akcije';
$strings['tour_home_topbar_actions_content'] = 'Ovdje možete pronaći ikone prečica kao što su kreiranje kurseva, vođena pomoć, tiketi i poruke, ovisno o vašoj ulozi.';
$strings['tour_home_menu_button_title'] = 'Dugme menija';
$strings['tour_home_menu_button_content'] = 'Koristite ovo dugme da brzo otvorite ili zatvorite bočni meni.';
$strings['tour_home_sidebar_title'] = 'Glavni meni';
$strings['tour_home_sidebar_content'] = 'Ovaj bočni meni daje pristup glavnim sekcijama platforme, ovisno o vašim dozvolama.';
$strings['tour_home_user_area_title'] = 'Korisničko područje';
$strings['tour_home_user_area_content'] = 'Ovdje možete pristupiti svom profilu, ličnim opcijama i odjaviti se.';
