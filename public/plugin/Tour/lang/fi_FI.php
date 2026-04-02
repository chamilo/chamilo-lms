<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Kierros';
$strings['plugin_comment'] = 'Tämä lisäosa näyttää ihmisille, miten Chamilo LMS:ää käytetään. Sinun täytyy aktivoida yksi alue (esim. "header-right"), jotta näytetään painike, jolla kierros käynnistyy.';

/* Strings for settings */
$strings['show_tour'] = 'Näytä kierros';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'Apublokkien näyttämiseen tarvittava JSON-muotoinen asetustiedosto sijaitsee tiedostossa <strong>plugin/tour/config/tour.json</strong>. <br> Katso lisätietoja README-tiedostosta.';

$strings['theme'] = 'Teema';
$strings['theme_help'] = 'Valitse <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Tyhjä käyttää oletusteemaa.';

/* Strings for plugin UI */
$strings['Skip'] = 'Ohita';
$strings['Next'] = 'Seuraava';
$strings['Prev'] = 'Edellinen';
$strings['Done'] = 'Valmis';
$strings['StartButtonText'] = 'Käynnistä kierros';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Tervetuloa <b>Chamilo LMS 1.9.x</b>:ään';
$strings['TheNavbarStep'] = 'Valikkopalkki, jossa on linkkejä portaalin pääosioihin';
$strings['TheRightPanelStep'] = 'Sivupaneeli';
$strings['TheUserImageBlock'] = 'Profiilikuvasi';
$strings['TheProfileBlock'] = 'Profiilityökalusi: <i>Sisäänposti</i>, <i>viestieditori</i>, <i>odottelevat kutsut</i>, <i>profiilin muokkaus</i>.';
$strings['TheHomePageStep'] = 'Tämä on alkusivu, jossa näet portaalin ilmoitukset, linkit ja kaiken muun tiedon, jonka ylläpitotiimi on määrittänyt.';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'Tämä alue näyttää kaikki kurssit (tai sessiot), joihin olet ilmoittautunut. Jos yhtään kurssia ei näy, mene kurssiluetteloon (katso valikko) tai ota yhteyttä portaalin ylläpitäjään.';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'Kalenterityökalu näyttää tulevien päivien, viikkojen tai kuukausien tapahtumat.';
$strings['AgendaTheActionBar'] = 'Voit näyttää tapahtumat listana kalenterin sijaan käyttämällä toimintonäppäimiä.';
$strings['AgendaTodayButton'] = 'Klikkaa "tänään"-painiketta nähdäksesi vain tämän päivän aikataulun.';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'Nykyinen kuukausi korostetaan aina kalenterinäkymässä.';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Voit vaihtaa näkymän päivä-, viikko- tai kuukausinäkymään näillä painikkeilla.';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Tämä alue näyttää edistymisesi, jos olet opiskelija, tai opiskelijoidesi edistymisen, jos olet opettaja.';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'Tältä sivulta löytyvät raportit ovat laajennettavissa ja antavat arvokasta tietoa oppimisestasi tai opettamisestasi.';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'Sosiaalialue mahdollistaa yhteydenpidon muiden alustan käyttäjien kanssa.';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'Valikko antaa pääsyn yksityisviesteihin, keskusteluihin, kiinnostuksenryhmiin jne.';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'Yhteenvetonäkymä tarjoaa tarkkaa tietoa havainnollisessa ja tiiviissä muodossa. Tällä hetkellä vain ylläpitäjillä on pääsy tähän toiminnallisuuteen.';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'Yhteenvetonäkymän paneelien aktivoimiseksi sinun täytyy ensin aktivoida mahdolliset paneelit lisäosien hallinnassa, minkä jälkeen palaa tänne ja valitse, mitkä paneelit *sinä* haluat nähdä yhteenvetonäkymässäsi.';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'Ylläpitopaneeli mahdollistaa kaikkien Chamilo-portaalisi resurssien hallinnan.';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'Käyttäjälohko mahdollistaa kaikkiin käyttäjiin liittyvien asioiden hallinnan.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'Kurssilohko antaa pääsyn kurssien luomiseen, muokkaamiseen jne. Muut lohkot on omistettu erityiskäyttöön.';


$strings['tour_home_featured_courses_title'] = 'Suositellut kurssit';
$strings['tour_home_featured_courses_content'] = 'Tämä osio näyttää suositellut kurssit kotisivullasi.';

$strings['tour_home_course_card_title'] = 'Kurssikortti';
$strings['tour_home_course_card_content'] = 'Jokainen kortti tiivistää yhden kurssin ja antaa nopean pääsyn sen keskeisiin tietoihin.';

$strings['tour_home_course_title_title'] = 'Kurssin nimi';
$strings['tour_home_course_title_content'] = 'Kurssin nimi auttaa tunnistamaan kurssin nopeasti ja voi avata lisätietoja alustan asetuksista riippuen.';

$strings['tour_home_teachers_title'] = 'Opettajat';
$strings['tour_home_teachers_content'] = 'Tämä alue näyttää kurssiin liittyvät opettajat tai käyttäjät.';

$strings['tour_home_rating_title'] = 'Arviointi ja palaute';
$strings['tour_home_rating_content'] = 'Täällä voit tarkistaa kurssin arvion ja tarvittaessa antaa oman äänesi.';

$strings['tour_home_main_action_title'] = 'Kurssin päätoiminto';
$strings['tour_home_main_action_content'] = 'Käytä tätä painiketta kurssille siirtymiseen, ilmoittautumiseen tai pääsyrajoitusten tarkistamiseen kurssin tilan mukaan.';

$strings['tour_home_show_more_title'] = 'Näytä lisää kursseja';
$strings['tour_home_show_more_content'] = 'Käytä tätä painiketta ladataksesi lisää kursseja ja jatkaaksesi luettelon selaamista kotisivulta.';

$strings['tour_my_courses_cards_title'] = 'Kurssikorttisi';
$strings['tour_my_courses_cards_content'] = 'Tämä sivu listaa ilmoittautumisiasi kurssit. Jokainen kortti antaa nopean pääsyn kurssille ja sen nykyiseen tilaan.';

$strings['tour_my_courses_image_title'] = 'Kurssikuva';
$strings['tour_my_courses_image_content'] = 'Kurssikuva auttaa tunnistamaan kurssin nopeasti. Useimmissa tapauksissa sen klikkaus avaa kurssin.';

$strings['tour_my_courses_title_title'] = 'Kurssin ja istunnon otsikko';
$strings['tour_my_courses_title_content'] = 'Täällä näet kurssin otsikon ja tarvittaessa siihen liittyvän istunnon nimen.';

$strings['tour_my_courses_progress_title'] = 'Oppimisen edistyminen';
$strings['tour_my_courses_progress_content'] = 'Tämä edistymispalkki näyttää, kuinka paljon kurssista on suoritettu.';

$strings['tour_my_courses_notifications_title'] = 'Uuden sisällön ilmoitukset';
$strings['tour_my_courses_notifications_content'] = 'Käytä tätä kellopainiketta tarkistaaksesi, onko kurssissa uutta sisältöä tai tuoreita päivityksiä. Kun se on korostettu, se auttaa huomaamaan nopeasti muutokset viimeisen käynnin jälkeen.';

$strings['tour_my_courses_footer_title'] = 'Opettajat ja kurssitiedot';
$strings['tour_my_courses_footer_content'] = 'Alatunniste voi näyttää opettajat, kielen ja muita kurssiin liittyviä hyödyllisiä tietoja.';

$strings['tour_my_courses_create_course_title'] = 'Luo kurssi';
$strings['tour_my_courses_create_course_content'] = 'Jos sinulla on lupa luoda kursseja, käytä tätä painiketta avataksesi kurssin luontilomakkeen suoraan tältä sivulta.';

$strings['tour_course_home_header_title'] = 'Kurssin yläosa';
$strings['tour_course_home_header_content'] = 'Tämä yläosa näyttää kurssin otsikon ja tarvittaessa aktiivisen istunnon. Se ryhmittelee myös pääopettajan toiminnot tältä sivulta.';

$strings['tour_course_home_title_title'] = 'Kurssin otsikko';
$strings['tour_course_home_title_content'] = 'Täältä tunnistat nykyisen kurssin nopeasti. Jos kurssi kuuluu istuntoon, istunnon otsikko näytetään sen vieressä.';

$strings['tour_course_home_teacher_tools_title'] = 'Opettajan työkalut';
$strings['tour_course_home_teacher_tools_content'] = 'Riippuen oikeuksistasi tämä alue voi sisältää opiskelijanäkymän vaihdon, johdannon muokkauksen, raportointiin pääsyn ja muita kurssinhallinnan toimintoja.';

$strings['tour_course_home_intro_title'] = 'Kurssin johdanto';
$strings['tour_course_home_intro_content'] = 'Tämä osio näyttää kurssin johdannon. Opettajat voivat käyttää sitä tavoitteiden, ohjeiden, linkkien tai oppijoille tärkeiden tietojen esittämiseen.';

$strings['tour_course_home_tools_controls_title'] = 'Työkalujen ohjaimet';
$strings['tour_course_home_tools_controls_content'] = 'Opettajat voivat käyttää näitä ohjaimia näyttääkseen tai piilotakseen kaikki työkalut kerralla tai ottaakseen käyttöön lajittelutilan kurssin työkalujen uudelleenjärjestelyyn.';

$strings['tour_course_home_tools_title'] = 'Kurssin työkalut';
$strings['tour_course_home_tools_content'] = 'Tämä alue sisältää pääkurssin työkalut, kuten asiakirjat, oppimispolut, harjoitukset, foorumit ja muut kurssissa saatavilla olevat resurssit.';

$strings['tour_course_home_tool_card_title'] = 'Työkalukortti';
$strings['tour_course_home_tool_card_content'] = 'Jokainen työkalukortti antaa pääsyn yhteen kurssin työkaluun. Käytä sitä siirtyäksesi nopeasti valittuun kurssin alueeseen.';

$strings['tour_course_home_tool_shortcut_title'] = 'Työkalun pikakuvake';
$strings['tour_course_home_tool_shortcut_content'] = 'Napsauta kuvakealuetta avataksesi valitun kurssin työkalun suoraan.';

$strings['tour_course_home_tool_name_title'] = 'Työkalun nimi';
$strings['tour_course_home_tool_name_content'] = 'Otsikko tunnistaa työkalun ja toimii myös suorana pääsylinkkinä.';

$strings['tour_course_home_tool_visibility_title'] = 'Työkalun näkyvyys';
$strings['tour_course_home_tool_visibility_content'] = 'Jos muokkaat kurssia, tämä painike antaa nopeasti muuttaa työkalun näkyvyyttä oppijoille.';
$strings['tour_admin_overview_title'] = 'Hallintapaneeli';
$strings['tour_admin_overview_content'] = 'Tämä sivu keskittää alustan päähallinta-alueet hallintateemoittain ryhmiteltynä.';

$strings['tour_admin_user_management_title'] = 'Käyttäjien hallinta';
$strings['tour_admin_user_management_content'] = 'Tästä lohkosta voit hallita rekisteröityneitä käyttäjiä, luoda tilejä, tuoda tai viedä käyttäjäluetteloita, muokata käyttäjiä, anonysoida tietoja ja hallita ryhmiä.';

$strings['tour_admin_course_management_title'] = 'Kurssien hallinta';
$strings['tour_admin_course_management_content'] = 'Tämä lohko antaa luoda ja hallita kursseja, tuoda tai viedä kurssiluetteloita, järjestää luokkia, nimetä käyttäjiä kursseille ja määrittää kurssiin liittyviä kenttiä ja työkaluja.';

$strings['tour_admin_sessions_management_title'] = 'Istuntojen hallinta';
$strings['tour_admin_sessions_management_content'] = 'Täällä voit hallita koulutusistuntoja, istuntoluokkia, tuontia ja vientiä, henkilöstöjohtajia, urapolkuja, ylennyksiä ja istuntoon liittyviä kenttiä.';

$strings['tour_admin_platform_management_title'] = 'Alustan hallinta';
$strings['tour_admin_platform_management_content'] = 'Käytä tätä lohkoa alustan globaaliin määrittämiseen, asetusten säätämiseen, ilmoitusten hallintaan, kieliin ja muihin keskeisiin hallintaoptioihin.';

$strings['tour_admin_tracking_title'] = 'Seuranta';
$strings['tour_admin_tracking_content'] = 'Tämä alue antaa pääsyn raportteihin, globaaleihin tilastoihin, oppimisanalytiikkaan ja muihin alustan seurantatietoihin.';

$strings['tour_admin_assessments_title'] = 'Arvioinnit';
$strings['tour_admin_assessments_content'] = 'Tämä lohko tarjoaa pääsyn alustalla saatavilla oleviin arviointiin liittyviin hallintatoimintoihin.';
$strings['tour_admin_skills_title'] = 'Taidot';
$strings['tour_admin_skills_content'] = 'Tämä lohko antaa hallita käyttäjien taitoja, taitojen tuontia, sijoituksia, tasoja ja taitoihin liittyviä arviointeja.';

$strings['tour_admin_system_title'] = 'Järjestelmä';
$strings['tour_admin_system_content'] = 'Täältä pääset palvelimen ja alustan ylläpitotyökaluihin, kuten järjestelmän tilaan, väliaikaisten tiedostojen siivoamiseen, tietojen täyttöön, sähköpostitesteihin ja teknisiin apuohjelmiin.';

$strings['tour_admin_rooms_title'] = 'Huoneet';
$strings['tour_admin_rooms_content'] = 'Tämä lohko antaa pääsyn huoneiden hallintaan, mukaan lukien toimipisteet, huoneet ja huoneiden saatavuushaku.';

$strings['tour_admin_security_title'] = 'Turvallisuus';
$strings['tour_admin_security_content'] = 'Käytä tätä aluetta kirjautumisyritysten tarkistamiseen, turvallisuusraportteihin ja alustalla saatavilla oleviin lisätyökaluihin.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Tämä lohko tarjoaa viralliset Chamilo-viitteet, käyttöoppaat, foorumit, asennusresurssit ja linkit palveluntarjoajiin sekä projektitietoihin.';

$strings['tour_admin_health_check_title'] = 'Terveyden tarkistus';
$strings['tour_admin_health_check_content'] = 'Tämä alue auttaa tarkistamaan alustan teknistä terveyttä listaamalla ympäristötarkistukset, kirjoitettavat polut ja tärkeät asennusvaroitukset.';

$strings['tour_admin_version_check_title'] = 'Version tarkistus';
$strings['tour_admin_version_check_content'] = 'Käytä tätä lohkoa portaalin rekisteröintiin ja version tarkistustoimintojen sekä julkisen alustalistauksen mahdollistamiseen.';

$strings['tour_admin_professional_support_title'] = 'Ammattimainen tuki';
$strings['tour_admin_professional_support_content'] = 'Tämä lohko selittää, miten ottaa yhteyttä virallisiin Chamilo-palveluntarjoajiin konsultointia, isännöintiä, koulutusta ja räätälöityä kehitystukea varten.';

$strings['tour_admin_news_title'] = 'Uutisia Chamiloselta';
$strings['tour_admin_news_content'] = 'Tämä osio näyttää viimeisimmät uutiset ja tiedotteet Chamilo-projektista.';
