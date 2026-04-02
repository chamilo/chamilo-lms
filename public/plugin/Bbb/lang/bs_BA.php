<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videokonferencija';
$strings['plugin_comment'] = 'Dodaj sobu za videokonferenciju u Chamilo kurs koristeći BigBlueButton (BBB)';

$strings['Videoconference'] = 'Videokonferencija';
$strings['MeetingOpened'] = 'Sastanak otvoren';
$strings['MeetingClosed'] = 'Sastanak zatvoren';
$strings['MeetingClosedComment'] = 'Ako ste zatražili snimanje sesija, snimak će biti dostupan na listi ispod kada bude potpuno generisan.';
$strings['CloseMeeting'] = 'Zatvori sastanak';

$strings['VideoConferenceXCourseX'] = 'Videokonferencija #%s kurs %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videokonferencija dodana u kalendar';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videokonferencija dodana u alat za linkove';

$strings['GoToTheVideoConference'] = 'Idi na videokonferenciju';

$strings['Records'] = 'Snimak';
$strings['Meeting'] = 'Sastanak';

$strings['ViewRecord'] = 'Pogledaj snimak';
$strings['CopyToLinkTool'] = 'Kopiraj u alat za linkove';

$strings['EnterConference'] = 'Uđi u videokonferenciju';
$strings['RecordList'] = 'Lista snimaka';
$strings['ServerIsNotRunning'] = 'Server za videokonferenciju ne radi';
$strings['ServerIsNotConfigured'] = 'Server za videokonferenciju nije konfigurisan';

$strings['XUsersOnLine'] = '%s korisnika/la online';

$strings['host'] = 'BigBlueButton host';
$strings['host_help'] = 'Ovo je naziv servera na kojem radi vaš BigBlueButton server.
Može biti localhost, IP adresa (npr. http://192.168.13.54) ili domen (npr. http://my.video.com).';

$strings['salt'] = 'BigBlueButton salt';
$strings['salt_help'] = 'Ovo je sigurnosni ključ vašeg BigBlueButton servera, koji će omogućiti vašem serveru da autentifikuje Chamilo instalaciju. Pogledajte BigBlueButton dokumentaciju da ga pronađete. Pokušajte bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Poruka dobrodošlice';
$strings['enable_global_conference'] = 'Omogući globalnu konferenciju';
$strings['enable_global_conference_per_user'] = 'Omogući globalnu konferenciju po korisniku';
$strings['enable_conference_in_course_groups'] = 'Omogući konferenciju u grupama kursa';
$strings['enable_global_conference_link'] = 'Omogući link do globalne konferencije na početnoj stranici';
$strings['disable_download_conference_link'] = 'Onemogući preuzimanje konferencije';
$strings['big_blue_button_record_and_store'] = 'Snimaj i čuvaj sesije';
$strings['bbb_enable_conference_in_groups'] = 'Dozvoli konferenciju u grupama';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Nema snimaka za sesije sastanka';
$strings['NoRecording'] = 'Nema snimka';
$strings['ClickToContinue'] = 'Klikni za nastavak';
$strings['NoGroup'] = 'Nema grupe';
$strings['UrlMeetingToShare'] = 'URL za dijeljenje';
$strings['AdminView'] = 'Prikaz za administratore';
$strings['max_users_limit'] = 'Maksimalni limit korisnika';
$strings['max_users_limit_help'] = 'Postavi na maksimalan broj korisnika koje želite dozvoliti po kursu ili sesiji-kursu. Ostavi prazno ili postavi na 0 da onemogućiš ovaj limit.';
$strings['MaxXUsersWarning'] = 'Ova konferencijska soba ima maksimalan broj od %s simultanih korisnika.';
$strings['MaxXUsersReached'] = 'Limit od %s simultanih korisnika je dostignut za ovu konferencijsku sobu. Pričekaj da se oslobodi jedno mjesto ili da počne druga konferencija da bi se pridružio.';
$strings['MaxXUsersReachedManager'] = 'Limit od %s simultanih korisnika je dostignut za ovu konferencijsku sobu. Da povećaš ovaj limit, kontaktiraj administratora platforme.';
$strings['MaxUsersInConferenceRoom'] = 'Maksimalni broj simultanih korisnika u konferencijskoj sobi';
$strings['global_conference_allow_roles'] = 'Link globalne konferencije vidljiv samo za ove uloge korisnika';
$strings['CreatedAt'] = 'Kreirano';
$strings['allow_regenerate_recording'] = 'Dozvoli regeneraciju snimka';
$strings['bbb_force_record_generation'] = 'Prisilno generiši snimak na kraju sastanka';
$strings['disable_course_settings'] = 'Onemogući postavke kursa';
$strings['UpdateAllCourses'] = 'Ažuriraj sve kurseve';
$strings['UpdateAllCourseSettings'] = 'Ažuriraj sve postavke kursa';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Ovo će odjednom ažurirati sve vaše postavke kursa.';
$strings['ThereIsNoVideoConferenceActive'] = 'Trenutno nema aktivne videokonferencije';
$strings['RoomClosed'] = 'Soba zatvorena';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Trajanje sastanka (u minutama)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Dozvoli studentima da pokrenu konferenciju u svojim grupama.';
$strings['hide_conference_link'] = 'Sakrij link konferencije u alatu kursa';
$strings['hide_conference_link_comment'] = 'Prikaži ili sakrij blok sa linkom do videokonferencije pored gumba za pridruživanje, da omogući korisnicima da ga kopiraju i zalijepe u drugi prozor preglednika ili pozovu druge. Autentifikacija će i dalje biti potrebna za pristup nejavnim konferencijama.';
$strings['delete_recordings_on_course_delete'] = 'Obriši snimke kada se kurs ukloni';
$strings['defaultVisibilityInCourseHomepage'] = 'Zadana vidljivost na početnoj stranici kursa';
$strings['ViewActivityDashboard'] = 'Pogledaj nadzornu ploču aktivnosti';
