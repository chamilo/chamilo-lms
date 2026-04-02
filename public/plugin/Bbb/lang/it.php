<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videoconferenza';
$strings['plugin_comment'] = 'Aggiungi una sala videoconferenza in un corso Chamilo usando BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videoconferenza';
$strings['MeetingOpened'] = 'Riunione aperta';
$strings['MeetingClosed'] = 'Riunione chiusa';
$strings['MeetingClosedComment'] = "Se hai richiesto la registrazione delle tue sessioni, la registrazione sarà disponibile nell'elenco qui sotto quando sarà stata completamente generata.";
$strings['CloseMeeting'] = 'Chiudi riunione';

$strings['VideoConferenceXCourseX'] = 'Videoconferenza #%s corso %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videoconferenza aggiunta al calendario';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videoconferenza aggiunta allo strumento link';

$strings['GoToTheVideoConference'] = 'Vai alla videoconferenza';

$strings['Records'] = 'Registrazione';
$strings['Meeting'] = 'Riunione';

$strings['ViewRecord'] = 'Visualizza registrazione';
$strings['CopyToLinkTool'] = 'Copia nello strumento link';

$strings['EnterConference'] = 'Entra nella videoconferenza';
$strings['RecordList'] = 'Elenco registrazioni';
$strings['ServerIsNotRunning'] = 'Il server videoconferenza non è in esecuzione';
$strings['ServerIsNotConfigured'] = 'Il server videoconferenza non è configurato';

$strings['XUsersOnLine'] = '%s utente(i) online';

$strings['host'] = 'Host BigBlueButton';
$strings['host_help'] = 'Questo è il nome del server dove è in esecuzione il tuo server BigBlueButton.
Potrebbe essere localhost, un indirizzo IP (es. http://192.168.13.54) o un nome di dominio (es. http://my.video.com).';

$strings['salt'] = 'Salt BigBlueButton';
$strings['salt_help'] = "Questa è la chiave di sicurezza del tuo server BigBlueButton, che permetterà al tuo server di autenticare l'installazione Chamilo. Consulta la documentazione BigBlueButton per individuarla. Prova bbb-conf --salt";

$strings['big_blue_button_welcome_message'] = 'Messaggio di benvenuto';
$strings['enable_global_conference'] = 'Abilita conferenza globale';
$strings['enable_global_conference_per_user'] = 'Abilita conferenza globale per utente';
$strings['enable_conference_in_course_groups'] = 'Abilita conferenza nei gruppi di corso';
$strings['enable_global_conference_link'] = 'Abilita il link alla conferenza globale nella homepage';
$strings['disable_download_conference_link'] = 'Disabilita download conferenza';
$strings['big_blue_button_record_and_store'] = 'Registra e archivia sessioni';
$strings['bbb_enable_conference_in_groups'] = 'Consenti conferenza nei gruppi';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Non ci sono registrazioni per le sessioni di riunione';
$strings['NoRecording'] = 'Nessuna registrazione';
$strings['ClickToContinue'] = 'Clicca per continuare';
$strings['NoGroup'] = 'Nessun gruppo';
$strings['UrlMeetingToShare'] = 'URL da condividere';
$strings['AdminView'] = 'Visualizza per amministratori';
$strings['max_users_limit'] = 'Limite utenti massimi';
$strings['max_users_limit_help'] = 'Imposta questo al numero massimo di utenti che desideri consentire per corso o sessione-corso. Lascia vuoto o imposta a 0 per disabilitare questo limite.';
$strings['MaxXUsersWarning'] = 'Questa sala di conferenza ha un numero massimo di %s utenti simultanei.';
$strings['MaxXUsersReached'] = "Il limite di %s utenti simultanei è stato raggiunto per questa sala di conferenza. Attendi che si liberi un posto o che inizi un'altra conferenza per unirti.";
$strings['MaxXUsersReachedManager'] = "Il limite di %s utenti simultanei è stato raggiunto per questa sala di conferenza. Per aumentare questo limite, contatta l'amministratore della piattaforma.";
$strings['MaxUsersInConferenceRoom'] = 'Utenti simultanei massimi in una sala di conferenza';
$strings['global_conference_allow_roles'] = 'Link conferenza globale visibile solo per questi ruoli utente';
$strings['CreatedAt'] = 'Creato il';
$strings['allow_regenerate_recording'] = 'Consenti rigenerazione registrazione';
$strings['bbb_force_record_generation'] = 'Forza generazione registrazione alla fine della riunione';
$strings['disable_course_settings'] = 'Disabilita impostazioni corso';
$strings['UpdateAllCourses'] = 'Aggiorna tutti i corsi';
$strings['UpdateAllCourseSettings'] = 'Aggiorna tutte le impostazioni dei corsi';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Questo aggiornerà tutte le impostazioni del tuo corso in una volta.';
$strings['ThereIsNoVideoConferenceActive'] = "Non c'è nessuna videoconferenza attualmente attiva";
$strings['RoomClosed'] = 'Stanza chiusa';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Durata riunione (in minuti)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Consenti agli studenti di avviare la conferenza nei loro gruppi.';
$strings['hide_conference_link'] = 'Nascondi il link della conferenza nello strumento del corso';
$strings['hide_conference_link_comment'] = "Mostra o nascondi un blocco con un link alla videoconferenza accanto al pulsante di partecipazione, per consentire agli utenti di copiarlo e incollarlo in un'altra finestra del browser o invitare altri. L'autenticazione sarà comunque necessaria per accedere alle conferenze non pubbliche.";
$strings['delete_recordings_on_course_delete'] = 'Elimina le registrazioni quando il corso viene rimosso';
$strings['defaultVisibilityInCourseHomepage'] = 'Visibilità predefinita nella home page del corso';
$strings['ViewActivityDashboard'] = 'Visualizza dashboard attività';
