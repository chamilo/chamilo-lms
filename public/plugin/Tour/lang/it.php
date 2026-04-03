<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'Tour';
$strings['plugin_comment'] = 'Questo plugin mostra alle persone come usare il tuo Chamilo LMS. Devi attivare una regione (es. "header-right") per mostrare il pulsante che avvia il tour.';

/* Strings for settings */
$strings['show_tour'] = 'Mostra il tour';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'La configurazione necessaria per mostrare i blocchi di aiuto, in formato JSON, si trova nel file <strong>plugin/tour/config/tour.json</strong>. <br> Vedi il file README per maggiori informazioni.';

$strings['theme'] = 'Tema';
$strings['theme_help'] = 'Scegli <i>nassim</i>, <i>nazanin</i>, <i>royal</i>. Vuoto per usare il tema predefinito.';

/* Strings for plugin UI */
$strings['Skip'] = 'Salta';
$strings['Next'] = 'Avanti';
$strings['Prev'] = 'Indietro';
$strings['Done'] = 'Fine';
$strings['StartButtonText'] = 'Avvia il tour';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = 'Benvenuti in <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = 'Barra del menu con link alle sezioni principali del portale';
$strings['TheRightPanelStep'] = 'Pannello laterale';
$strings['TheUserImageBlock'] = 'La tua foto del profilo';
$strings['TheProfileBlock'] = 'I tuoi strumenti del profilo: <i>Casella di posta</i>, <i>compilatore di messaggi</i>, <i>inviti in sospeso</i>, <i>modifica profilo</i>.';
$strings['TheHomePageStep'] = 'Questa è la homepage iniziale dove troverai gli annunci del portale, link e qualsiasi informazione configurata dal team di amministrazione.';

// if body class = section-mycourses
$strings['YourCoursesList'] = "Questa area mostra i diversi corsi (o sessioni) a cui sei iscritto. Se non appare nessun corso, vai al catalogo dei corsi (vedi menu) o discutilo con l'amministratore del portale";

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'Lo strumento agenda ti permette di vedere gli eventi programmati per i prossimi giorni, settimane o mesi.';
$strings['AgendaTheActionBar'] = 'Puoi decidere di mostrare gli eventi come lista, anziché in vista calendario, usando le icone di azione fornite';
$strings['AgendaTodayButton'] = 'Clicca il pulsante "oggi" per vedere solo la programmazione di oggi';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'Il mese corrente è sempre evidenziato nella vista calendario';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'Puoi passare alla vista giornaliera, settimanale o mensile cliccando uno di questi pulsanti';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = 'Questa area ti permette di controllare i tuoi progressi se sei uno studente, o i progressi dei tuoi studenti se sei un insegnante';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'I report forniti in questa schermata sono estensibili e possono fornirti preziose informazioni sul tuo apprendimento o insegnamento';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = "L'area sociale ti permette di entrare in contatto con altri utenti della piattaforma";
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'Il menu ti dà accesso a una serie di schermate che ti permettono di partecipare a messaggistica privata, chat, gruppi di interesse, ecc.';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'La Dashboard ti permette di ottenere informazioni molto specifiche in un formato illustrato e condensato. Solo gli amministratori hanno accesso a questa funzionalità al momento';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'Per abilitare i pannelli Dashboard, devi prima attivare i possibili pannelli nella sezione admin per i plugin, poi tornare qui e scegliere quali pannelli *tu* vuoi vedere sulla tua dashboard';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = 'Il pannello di amministrazione ti permette di gestire tutte le risorse nel tuo portale Chamilo';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'Il blocco utenti ti permette di gestire tutte le cose relative agli utenti.';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'Il blocco corsi ti dà accesso alla creazione, modifica dei corsi, ecc. Altri blocchi sono dedicati a usi specifici.';


$strings['tour_home_featured_courses_title'] = 'Corsi in evidenza';
$strings['tour_home_featured_courses_content'] = 'Questa sezione mostra i corsi in evidenza disponibili sulla tua homepage.';

$strings['tour_home_course_card_title'] = 'Scheda corso';
$strings['tour_home_course_card_content'] = 'Ogni scheda riassume un corso e ti dà accesso rapido alle sue informazioni principali.';

$strings['tour_home_course_title_title'] = 'Titolo del corso';
$strings['tour_home_course_title_content'] = 'Il titolo del corso ti aiuta a identificare rapidamente il corso e può anche aprire più informazioni a seconda delle impostazioni della piattaforma.';

$strings['tour_home_teachers_title'] = 'Insegnanti';
$strings['tour_home_teachers_content'] = 'Questa area mostra gli insegnanti o utenti associati al corso.';

$strings['tour_home_rating_title'] = 'Valutazione e feedback';
$strings['tour_home_rating_content'] = 'Qui puoi rivedere la valutazione del corso e, quando consentito, inviare il tuo voto.';

$strings['tour_home_main_action_title'] = 'Azione principale del corso';
$strings['tour_home_main_action_content'] = 'Usa questo pulsante per entrare nel corso, iscriverti o rivedere le restrizioni di accesso a seconda dello stato del corso.';

$strings['tour_home_show_more_title'] = 'Mostra più corsi';
$strings['tour_home_show_more_content'] = 'Usa questo pulsante per caricare più corsi e continuare a esplorare il catalogo dalla homepage.';

$strings['tour_my_courses_cards_title'] = 'Le tue schede dei corsi';
$strings['tour_my_courses_cards_content'] = 'Questa pagina elenca i corsi a cui sei iscritto. Ogni scheda ti dà accesso rapido al corso e al suo stato corrente.';

$strings['tour_my_courses_image_title'] = 'Immagine del corso';
$strings['tour_my_courses_image_content'] = "L'immagine del corso ti aiuta a identificare rapidamente il corso. Nella maggior parte dei casi, cliccarla apre il corso.";

$strings['tour_my_courses_title_title'] = 'Titolo corso e sessione';
$strings['tour_my_courses_title_content'] = 'Qui puoi vedere il titolo del corso e, se applicabile, il nome della sessione associata a quel corso.';

$strings['tour_my_courses_progress_title'] = 'Progresso di apprendimento';
$strings['tour_my_courses_progress_content'] = 'Questa barra di progresso mostra quanto del corso hai completato.';

$strings['tour_my_courses_notifications_title'] = 'Notifiche nuovo contenuto';
$strings['tour_my_courses_notifications_content'] = "Usa questo pulsante a forma di campanella per verificare se il corso ha nuovo contenuto o aggiornamenti recenti. Quando è evidenziato, ti aiuta a individuare rapidamente le modifiche dall'ultimo accesso.";

$strings['tour_my_courses_footer_title'] = 'Docenti e dettagli corso';
$strings['tour_my_courses_footer_content'] = 'Il footer può mostrare docenti, lingua e altre informazioni utili relative al corso.';

$strings['tour_my_courses_create_course_title'] = 'Crea un corso';
$strings['tour_my_courses_create_course_content'] = 'Se hai il permesso di creare corsi, usa questo pulsante per aprire il modulo di creazione del corso direttamente da questa pagina.';

$strings['tour_course_home_header_title'] = 'Intestazione corso';
$strings['tour_course_home_header_content'] = 'Questa intestazione mostra il titolo del corso e, se applicabile, la sessione attiva. Raggruppa anche le principali azioni del docente disponibili su questa pagina.';

$strings['tour_course_home_title_title'] = 'Titolo corso';
$strings['tour_course_home_title_content'] = 'Qui puoi identificare rapidamente il corso corrente. Se il corso appartiene a una sessione, il titolo della sessione è visualizzato accanto.';

$strings['tour_course_home_teacher_tools_title'] = 'Strumenti docente';
$strings['tour_course_home_teacher_tools_content'] = "A seconda dei tuoi permessi, quest'area può includere l'interruttore vista studente, modifica introduzione, accesso report e altre azioni di gestione del corso.";

$strings['tour_course_home_intro_title'] = 'Introduzione corso';
$strings['tour_course_home_intro_content'] = "Questa sezione visualizza l'introduzione del corso. I docenti possono usarla per presentare obiettivi, indicazioni, link o informazioni chiave per gli studenti.";

$strings['tour_course_home_tools_controls_title'] = 'Controlli strumenti';
$strings['tour_course_home_tools_controls_content'] = 'I docenti possono usare questi controlli per mostrare o nascondere tutti gli strumenti contemporaneamente, o attivare la modalità di ordinamento per riorganizzare gli strumenti del corso.';

$strings['tour_course_home_tools_title'] = 'Strumenti corso';
$strings['tour_course_home_tools_content'] = "Quest'area contiene i principali strumenti del corso, come documenti, percorsi di apprendimento, esercizi, forum e altre risorse disponibili nel corso.";

$strings['tour_course_home_tool_card_title'] = 'Scheda strumento';
$strings['tour_course_home_tool_card_content'] = "Ogni scheda strumento dà accesso a uno strumento del corso. Usala per entrare rapidamente nell'area selezionata del corso.";

$strings['tour_course_home_tool_shortcut_title'] = 'Scorciatoia strumento';
$strings['tour_course_home_tool_shortcut_content'] = "Clicca sull'area dell'icona per aprire direttamente lo strumento del corso selezionato.";

$strings['tour_course_home_tool_name_title'] = 'Nome strumento';
$strings['tour_course_home_tool_name_content'] = 'Il titolo identifica lo strumento e funge anche da link di accesso diretto.';

$strings['tour_course_home_tool_visibility_title'] = 'Visibilità strumento';
$strings['tour_course_home_tool_visibility_content'] = 'Se stai modificando il corso, questo pulsante ti permette di cambiare rapidamente la visibilità dello strumento per gli studenti.';
$strings['tour_admin_overview_title'] = 'Dashboard amministrazione';
$strings['tour_admin_overview_content'] = 'Questa pagina centralizza le principali aree di amministrazione della piattaforma, raggruppate per argomento di gestione.';

$strings['tour_admin_user_management_title'] = 'Gestione utenti';
$strings['tour_admin_user_management_content'] = 'Da questo blocco puoi gestire gli utenti registrati, creare account, importare o esportare elenchi utenti, modificare utenti, anonimizzare dati e gestire classi.';

$strings['tour_admin_course_management_title'] = 'Gestione corsi';
$strings['tour_admin_course_management_content'] = 'Questo blocco ti permette di creare e gestire corsi, importare o esportare elenchi corsi, organizzare categorie, assegnare utenti ai corsi e configurare campi e strumenti relativi al corso.';

$strings['tour_admin_sessions_management_title'] = 'Gestione sessioni';
$strings['tour_admin_sessions_management_content'] = 'Qui puoi gestire sessioni formative, categorie di sessione, import ed export, direttori HR, carriere, promozioni e campi relativi alla sessione.';

$strings['tour_admin_platform_management_title'] = 'Gestione piattaforma';
$strings['tour_admin_platform_management_content'] = 'Usa questo blocco per configurare la piattaforma globalmente, regolare impostazioni, gestire annunci, lingue e altre opzioni di amministrazione centrale.';

$strings['tour_admin_tracking_title'] = 'Tracciamento';
$strings['tour_admin_tracking_content'] = "Quest'area dà accesso a report, statistiche globali, analisi di apprendimento e altri dati di tracciamento su tutta la piattaforma.";

$strings['tour_admin_assessments_title'] = 'Valutazioni';
$strings['tour_admin_assessments_content'] = 'Questo blocco fornisce accesso alle funzionalità di amministrazione relative alle valutazioni disponibili sulla piattaforma.';
$strings['tour_admin_skills_title'] = 'Competenze';
$strings['tour_admin_skills_content'] = 'Questo blocco ti permette di gestire competenze utente, import competenze, classifiche, livelli e valutazioni relative alle competenze.';

$strings['tour_admin_system_title'] = 'Sistema';
$strings['tour_admin_system_content'] = 'Qui puoi accedere a strumenti di manutenzione server e piattaforma, come stato del sistema, pulizia file temporanei, riempitore dati, test e-mail e utilità tecniche.';

$strings['tour_admin_rooms_title'] = 'Aule';
$strings['tour_admin_rooms_content'] = 'Questo blocco dà accesso alle funzionalità di gestione aule, incluse filiali, aule e ricerca disponibilità aule.';

$strings['tour_admin_security_title'] = 'Sicurezza';
$strings['tour_admin_security_content'] = 'Usa questa area per rivedere i tentativi di accesso, report relativi alla sicurezza e strumenti di sicurezza aggiuntivi disponibili sulla piattaforma.';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'Questo blocco fornisce riferimenti ufficiali di Chamilo, guide utente, forum, risorse di installazione e link a fornitori di servizi e informazioni sul progetto.';

$strings['tour_admin_health_check_title'] = 'Controllo di integrità';
$strings['tour_admin_health_check_content'] = "Questa area ti aiuta a verificare lo stato tecnico della piattaforma elencando controlli dell'ambiente, percorsi scrivibili e avvisi importanti di installazione.";

$strings['tour_admin_version_check_title'] = 'Controllo versione';
$strings['tour_admin_version_check_content'] = 'Usa questo blocco per registrare il tuo portale e abilitare le funzionalità di controllo versione e opzioni di elenco pubblico della piattaforma.';

$strings['tour_admin_professional_support_title'] = 'Supporto professionale';
$strings['tour_admin_professional_support_content'] = 'Questo blocco spiega come contattare i fornitori ufficiali di Chamilo per consulenza, hosting, formazione e supporto per sviluppo personalizzato.';

$strings['tour_admin_news_title'] = 'Novità da Chamilo';
$strings['tour_admin_news_content'] = 'Questa sezione mostra le ultime notizie e annunci dal progetto Chamilo.';

$strings['tour_home_topbar_logo_title'] = 'Logo della piattaforma';
$strings['tour_home_topbar_logo_content'] = 'Questo logo ti riporta alla pagina iniziale della piattaforma.';
$strings['tour_home_topbar_actions_title'] = 'Azioni rapide';
$strings['tour_home_topbar_actions_content'] = 'Qui trovi icone di accesso rapido come creazione dei corsi, aiuto guidato, ticket e messaggi, in base al tuo ruolo.';
$strings['tour_home_menu_button_title'] = 'Pulsante del menu';
$strings['tour_home_menu_button_content'] = 'Usa questo pulsante per aprire o chiudere rapidamente il menu laterale.';
$strings['tour_home_sidebar_title'] = 'Menu principale';
$strings['tour_home_sidebar_content'] = 'Questo menu laterale dà accesso alle sezioni principali della piattaforma, in base ai tuoi permessi.';
$strings['tour_home_user_area_title'] = 'Area utente';
$strings['tour_home_user_area_content'] = 'Qui puoi accedere al tuo profilo, alle opzioni personali e disconnetterti.';
