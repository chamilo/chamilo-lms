<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Ekskursija';
$strings['plugin_comment'] = 'Šis įskiepis rodo žmonėms, kaip naudotis jūsų Chamilo LMS. Turite suaktyvinti vieną regioną (pvz., „header-right“), kad būtų rodomas mygtukas, leidžiantis pradėti ekskursiją.';

/* Strings for settings */
$strings['show_tour'] = 'Rodyti ekskursiją';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'Būtinoji pagalbos blokų rodymo konfigūracija JSON formatu yra faile <strong>plugin/tour/config/tour.json</strong>. <br> Daugiau informacijos rasite README faile.';

$strings['theme'] = 'Tema';
$strings['theme_help'] = 'Pasirinkite <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Palikite tuščią, kad naudotų numatytąją temą.';

/* Strings for plugin UI */
$strings['Skip'] = 'Praleisti';
$strings['Next'] = 'Kitas';
$strings['Prev'] = 'Ankstesnis';
$strings['Done'] = 'Atlikta';
$strings['StartButtonText'] = 'Pradėti ekskursiją';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Sveiki atvykę į <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Meniu juosta su nuorodomis į portalo pagrindines dalis';
$strings['TheRightPanelStep'] = 'Šoninis skydelis';
$strings['TheUserImageBlock'] = 'Jūsų profilio nuotrauka';
$strings['TheProfileBlock'] = 'Jūsų profilio įrankiai: <i>Pašto dėžutė</i>, <i>žinučių kūrėjas</i>, <i>laukiantys pakvietimai</i>, <i>profilio redagavimas</i>.';
$strings['TheHomePageStep'] = 'Tai pradinis pradžios puslapis, kuriame rasite portalo pranešimus, nuorodas ir bet kokią administracijos komandos sukonfigūruotą informaciją.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Šioje srityje rodomi skirtingi kursai (arba sesijos), į kuriuos esate užsiregistravę. Jei nerodomas joks kursas, eikite į kursų katalogą (žr. meniu) arba aptarkite tai su savo portalo administratoriumi.';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'Darbotvarkės įrankis leidžia pamatyti, kokie renginiai suplanuoti artimiausioms dienoms, savaitėms ar mėnesiams.';
$strings['AgendaTheActionBar'] = 'Galite nuspręsti rodyti renginius kaip sąrašą, o ne kalendoriaus vaizdą, naudodami pateiktus veiksenos piktogramas.';
$strings['AgendaTodayButton'] = 'Spustelėkite „šiandien“ mygtuką, kad pamatytumėte tik šiandienos tvarkaraštį.';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'Dabartinis mėnuo kalendoriaus vaizde visada rodomas paryškintas.';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Galite perjungti vaizdą į dienos, savaitės ar mėnesio, spustelėdami vieną iš šių mygtukų.';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Ši sritis leidžia patikrinti savo pažangą, jei esate studentas, arba savo studentų pažangą, jei esate dėstytojas.';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Šiame ekrane pateikiamos ataskaitos yra plečiamos ir gali suteikti labai vertingų įžvalgų apie jūsų mokymąsi ar dėstymą.';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'Socialinė sritis leidžia susisiekti su kitais platformos vartotojais.';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'Meniu suteikia prieigą prie kelių ekranų, leidžiančių dalyvauti privačiose žinutėse, pokalbiuose, interesų grupėse ir kt.';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'Prietaisų skydas leidžia gauti labai konkrečią informaciją iliustruotu ir sutrumpintu formatu. Šiuo metu prieiga prie šios funkcijos yra tik administratoriams.';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'Norėdami įjungti prietaisų skydo skydelius, pirmiausia turite suaktyvinti galimus skydelius administravimo skyriuje skirtame įskiepiams, tada grįžkite čia ir pasirinkite, kurie skydeliai *jūs* norite matyti savo prietaisų skydelyje.';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'Administravimo skydelis leidžia valdyti visus išteklius jūsų Chamilo portale.';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'Vartotojų blokas leidžia valdyti viską, kas susiję su vartotojais.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'Kursų blokas suteikia prieigą prie kurso kūrimo, redagavimo ir kt. Kiti blokai taip pat skirti specifiniams naudojimams.';


$strings['tour_home_featured_courses_title'] = 'Rekomenduojami kursai';
$strings['tour_home_featured_courses_content'] = 'Ši dalis rodo rekomenduojamus kursus, prieinamus jūsų pradžios puslapyje.';

$strings['tour_home_course_card_title'] = 'Kursų kortelė';
$strings['tour_home_course_card_content'] = 'Kiekviena kortelė apibendrina vieną kursą ir suteikia greitą prieigą prie pagrindinės jo informacijos.';

$strings['tour_home_course_title_title'] = 'Kursų pavadinimas';
$strings['tour_home_course_title_content'] = 'Kursų pavadinimas padeda greitai identifikuoti kursą ir taip pat gali atverti daugiau informacijos priklausomai nuo platformos nustatymų.';

$strings['tour_home_teachers_title'] = 'Dėstytojai';
$strings['tour_home_teachers_content'] = 'Ši sritis rodo dėstytojus ar vartotojus, susijusius su kursu.';

$strings['tour_home_rating_title'] = 'Įvertinimas ir atsiliepimai';
$strings['tour_home_rating_content'] = 'Čia galite peržiūrėti kurso įvertinimą ir, kai leidžiama, pateikti savo balsą.';

$strings['tour_home_main_action_title'] = 'Pagrindinis kurso veiksmas';
$strings['tour_home_main_action_content'] = 'Naudokite šį mygtuką, kad įeitumėte į kursą, užsiregistruotumėte ar peržiūrėtumėte prieigos apribojimus priklausomai nuo kurso būsenos.';

$strings['tour_home_show_more_title'] = 'Rodyti daugiau kursų';
$strings['tour_home_show_more_content'] = 'Naudokite šį mygtuką, kad įkeltumėte daugiau kursų ir tęstumėte katalogą tyrinėti iš pradžios puslapio.';

$strings['tour_my_courses_cards_title'] = 'Jūsų kursų kortelės';
$strings['tour_my_courses_cards_content'] = 'Šis puslapis išvardina kursus, į kuriuos esate užsiregistravę. Kiekviena kortelė suteikia greitą prieigą prie kurso ir jo dabartinės būsenos.';

$strings['tour_my_courses_image_title'] = 'Kursų paveikslėlis';
$strings['tour_my_courses_image_content'] = 'Kursų paveikslėlis padeda greitai identifikuoti kursą. Dažniausiai spustelėjus jį atsidaro kursas.';

$strings['tour_my_courses_title_title'] = 'Kurso ir sesijos pavadinimas';
$strings['tour_my_courses_title_content'] = 'Čia matomas kurso pavadinimas ir, jei taikoma, su tuo kursu susijęs sesijos pavadinimas.';

$strings['tour_my_courses_progress_title'] = 'Mokymosi pažanga';
$strings['tour_my_courses_progress_content'] = 'Ši pažangos juosta rodo, kiek kurso jau užbaigėte.';

$strings['tour_my_courses_notifications_title'] = 'Naujo turinio pranešimai';
$strings['tour_my_courses_notifications_content'] = 'Naudokite šį varpelio mygtuką, kad patikrintumėte, ar kursas turi naujo turinio ar neseniai atnaujinimų. Kai paryškintas, jis padeda greitai pastebėti pokyčius nuo paskutinio prisijungimo.';

$strings['tour_my_courses_footer_title'] = 'Dėstytojai ir kurso informacija';
$strings['tour_my_courses_footer_content'] = 'Apačioje rodomi dėstytojai, kalba ir kita naudinga su kursu susijusi informacija.';

$strings['tour_my_courses_create_course_title'] = 'Sukurti kursą';
$strings['tour_my_courses_create_course_content'] = 'Jei turite leidimą kurti kursus, naudokite šį mygtuką, kad iš šio puslapio tiesiogiai atidarytumėte kurso kūrimo formą.';

$strings['tour_course_home_header_title'] = 'Kurso antraštė';
$strings['tour_course_home_header_content'] = 'Ši antraštė rodo kurso pavadinimą ir, jei taikoma, aktyvią sesiją. Ji taip pat grupuoja pagrindinius dėstytojų veiksmus, prieinamus šiame puslapyje.';

$strings['tour_course_home_title_title'] = 'Kurso pavadinimas';
$strings['tour_course_home_title_content'] = 'Čia galite greitai identifikuoti einamąjį kursą. Jei kursas priklauso sesijai, šalia rodomas sesijos pavadinimas.';

$strings['tour_course_home_teacher_tools_title'] = 'Dėstytojo įrankiai';
$strings['tour_course_home_teacher_tools_content'] = 'Priklausomai nuo jūsų teisių, ši sritis gali apimti studento vaizdo perjungimą, įvado redagavimą, ataskaitų prieigą ir papildomus kurso valdymo veiksmus.';

$strings['tour_course_home_intro_title'] = 'Kurso įvadas';
$strings['tour_course_home_intro_content'] = 'Ši dalis rodo kurso įvadą. Dėstytojai gali naudoti jį pristatyti tikslus, gaires, nuorodas ar pagrindinę informaciją besimokantiesiems.';

$strings['tour_course_home_tools_controls_title'] = 'Įrankių valdikliai';
$strings['tour_course_home_tools_controls_content'] = 'Dėstytojai gali naudoti šiuos valdiklius, kad vienu metu rodytų ar slėptų visus įrankius arba įjungtų rūšiavimo režimą, kad pertvarkytų kurso įrankius.';

$strings['tour_course_home_tools_title'] = 'Kurso įrankiai';
$strings['tour_course_home_tools_content'] = 'Šioje srityje yra pagrindiniai kurso įrankiai, tokie kaip dokumentai, mokymosi keliai, pratimai, forumai ir kiti kurse prieinami ištekliai.';

$strings['tour_course_home_tool_card_title'] = 'Įrankio kortelė';
$strings['tour_course_home_tool_card_content'] = 'Kiekviena įrankio kortelė suteikia prieigą prie vieno kurso įrankio. Naudokite ją, kad greitai patektumėte į pasirinktą kurso sritį.';

$strings['tour_course_home_tool_shortcut_title'] = 'Įrankio nuoroda';
$strings['tour_course_home_tool_shortcut_content'] = 'Spustelėkite piktogramos sritį, kad tiesiogiai atidarytumėte pasirinktą kurso įrankį.';

$strings['tour_course_home_tool_name_title'] = 'Įrankio pavadinimas';
$strings['tour_course_home_tool_name_content'] = 'Pavadinimas identifikuoja įrankį ir taip pat veikia kaip tiesioginė prieigos nuoroda.';

$strings['tour_course_home_tool_visibility_title'] = 'Įrankio matomumas';
$strings['tour_course_home_tool_visibility_content'] = 'Jei redaguojate kursą, šis mygtukas leidžia greitai pakeisti įrankio matomumą besimokantiesiems.';
$strings['tour_admin_overview_title'] = 'Administravimo skydelis';
$strings['tour_admin_overview_content'] = 'Šis puslapis centralizuoja pagrindines platformos administravimo sritis, sugrupuotas pagal valdymo temą.';

$strings['tour_admin_user_management_title'] = 'Vartotojų valdymas';
$strings['tour_admin_user_management_content'] = 'Iš šio bloko galite valdyti registruotus vartotojus, kurti paskyras, importuoti ar eksportuoti vartotojų sąrašus, redaguoti vartotojus, anonimizuoti duomenis ir valdyti klases.';

$strings['tour_admin_course_management_title'] = 'Kursų valdymas';
$strings['tour_admin_course_management_content'] = 'Šis blokas leidžia kurti ir valdyti kursus, importuoti ar eksportuoti kursų sąrašus, organizuoti kategorijas, priskirti vartotojus kursams ir konfigūruoti su kursu susijusius laukus bei įrankius.';

$strings['tour_admin_sessions_management_title'] = 'Sesijų valdymas';
$strings['tour_admin_sessions_management_content'] = 'Čia galite valdyti mokymų sesijas, sesijų kategorijas, importus ir eksportus, HR direktorius, karjeras, paaukštinimus ir su sesijomis susijusius laukus.';

$strings['tour_admin_platform_management_title'] = 'Platformos valdymas';
$strings['tour_admin_platform_management_content'] = 'Naudokite šį bloką, kad globaliai konfigūruotumėte platformą, koreguotumėte nustatymus, valdytumėte skelbimus, kalbas ir kitus centrinės administracijos pasirinkimus.';

$strings['tour_admin_tracking_title'] = 'Sekimas';
$strings['tour_admin_tracking_content'] = 'Ši sritis suteikia prieigą prie ataskaitų, globalios statistikos, mokymosi analitikos ir kitų sekimų duomenų visoje platformoje.';

$strings['tour_admin_assessments_title'] = 'Vertinimai';
$strings['tour_admin_assessments_content'] = 'Šis blokas suteikia prieigą prie su vertinimais susijusių administravimo funkcijų, prieinamų platformoje.';
$strings['tour_admin_skills_title'] = 'Įgūdžiai';
$strings['tour_admin_skills_content'] = 'Šis blokas leidžia valdyti vartotojų įgūdžius, įgūdžių importus, reitingus, lygius ir su įgūdžiais susijusius vertinimus.';

$strings['tour_admin_system_title'] = 'Sistema';
$strings['tour_admin_system_content'] = 'Čia galite pasiekti serverio ir platformos priežiūros įrankius, tokius kaip sistemos būklė, laikinų failų valymas, duomenų užpildymas, el. pašto testai ir techninės priemonės.';

$strings['tour_admin_rooms_title'] = 'Kambariai';
$strings['tour_admin_rooms_content'] = 'Šis blokas suteikia prieigą prie kambarių valdymo funkcijų, įskaitant padalinius, kambarius ir kambarių prieinamumo paiešką.';

$strings['tour_admin_security_title'] = 'Sauga';
$strings['tour_admin_security_content'] = 'Naudokite šią sritį prisijungimo bandymams peržiūrėti, saugai susijusioms ataskaitoms ir papildomoms platformoje prieinamoms saugumo priemonėms.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Šis blokas teikia oficialias Chamilo nuorodas, vartotojo vadovus, forumus, diegimo išteklius ir nuorodas į paslaugų teikėjus bei projekto informaciją.';

$strings['tour_admin_health_check_title'] = 'Sveikatos patikra';
$strings['tour_admin_health_check_content'] = 'Ši sritis padeda peržiūrėti platformos techninę būklę, išvardindama aplinkos patikras, rašomų kelių ir svarbius diegimo įspėjimus.';

$strings['tour_admin_version_check_title'] = 'Versijos patikra';
$strings['tour_admin_version_check_content'] = 'Naudokite šį bloką portalui registruoti ir versijų tikrinimo funkcijų bei viešojo platformos sąrašo parinkčių įjungimui.';

$strings['tour_admin_professional_support_title'] = 'Profesionalus palaikymas';
$strings['tour_admin_professional_support_content'] = 'Šis blokas paaiškina, kaip susisiekti su oficialiais Chamilo teikėjais konsultacijoms, talpinimui, mokymams ir nestandartinio kūrimo palaikymui.';

$strings['tour_admin_news_title'] = 'Naujienos iš Chamilo';
$strings['tour_admin_news_content'] = 'Ši dalis rodo naujausias Chamilo projekto naujienas ir pranešimus.';
