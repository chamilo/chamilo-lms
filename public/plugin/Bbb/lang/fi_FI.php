<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videoneuvottelu';
$strings['plugin_comment'] = 'Lisää videoneuvotteluhuone Chamilo-kurssille käyttäen BigBlueButtonia (BBB)';

$strings['Videoconference'] = 'Videoneuvottelu';
$strings['MeetingOpened'] = 'Kokous avattu';
$strings['MeetingClosed'] = 'Kokous suljettu';
$strings['MeetingClosedComment'] = 'Jos olet pyytänyt istuntojasi tallennettavaksi, tallenne on saatavilla alla olevassa luettelossa, kun se on täysin tuotettu.';
$strings['CloseMeeting'] = 'Sulje kokous';

$strings['VideoConferenceXCourseX'] = 'Videoneuvottelu #%s kurssi %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videoneuvottelu lisätty kalenteriin';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videoneuvottelu lisätty linkkityökaluun';

$strings['GoToTheVideoConference'] = 'Siirry videoneuvotteluun';

$strings['Records'] = 'Tallenne';
$strings['Meeting'] = 'Kokous';

$strings['ViewRecord'] = 'Näytä tallenne';
$strings['CopyToLinkTool'] = 'Kopioi linkkityökaluun';

$strings['EnterConference'] = 'Liity videoneuvotteluun';
$strings['RecordList'] = 'Talleneluettelo';
$strings['ServerIsNotRunning'] = 'Videoneuvottelupalvelin ei ole käynnissä';
$strings['ServerIsNotConfigured'] = 'Videoneuvottelupalvelinta ei ole konfiguroitu';

$strings['XUsersOnLine'] = '%s käyttäjää(a) verkossa';

$strings['host'] = 'BigBlueButton-isäntä';
$strings['host_help'] = 'Tämä on palvelimen nimi, jossa BigBlueButton-palvelimesi on käynnissä.
Se voi olla localhost, IP-osoite (esim. http://192.168.13.54) tai verkkotunnus (esim. http://my.video.com).';

$strings['salt'] = 'BigBlueButton-suolat';
$strings['salt_help'] = 'Tämä on BigBlueButton-palvelimesi turvallisuusavain, joka mahdollistaa palvelimesi Chamilo-asennuksen tunnistautumisen. Katso BigBlueButton-dokumentaatiosta sen sijainti. Kokeile bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Tervetuloviesti';
$strings['enable_global_conference'] = 'Ota käyttöön globaali konferenssi';
$strings['enable_global_conference_per_user'] = 'Ota käyttöön globaali konferenssi käyttäjää kohden';
$strings['enable_conference_in_course_groups'] = 'Ota konferenssi käyttöön kurssiryhmissä';
$strings['enable_global_conference_link'] = 'Ota linkki globaaliin konferenssiin käyttöön etusivulla';
$strings['disable_download_conference_link'] = 'Poista konferenssin lataus käytöstä';
$strings['big_blue_button_record_and_store'] = 'Tallenna ja säilytä istunnot';
$strings['bbb_enable_conference_in_groups'] = 'Salli konferenssi ryhmissä';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Kokousistunnoille ei ole tallenteita';
$strings['No recording'] = 'Ei tallenteita';
$strings['ClickToContinue'] = 'Klikkaa jatkaaksesi';
$strings['NoGroup'] = 'Ei ryhmää';
$strings['UrlMeetingToShare'] = 'Jaettava URL';
$strings['AdminView'] = 'Ylläpitäjän näkymä';
$strings['max_users_limit'] = 'Käyttäjien enimmäismäärä';
$strings['max_users_limit_help'] = 'Aseta tämä arvoon, joka vastaa suurinta sallittua käyttäjämäärää kurssia tai istunto-kurssia kohden. Jätä tyhjäksi tai aseta 0 poistaaksesi rajoituksen.';
$strings['MaxXUsersWarning'] = 'Tässä konferenssihuoneessa on enintään %s samanaikaista käyttäjää.';
$strings['MaxXUsersReached'] = 'Tämän konferenssihuoneen %s samanaikaisten käyttäjien raja on saavutettu. Odota, kunnes yksi paikka vapautuu tai toinen konferenssi alkaa, jotta voit liittyä.';
$strings['MaxXUsersReachedManager'] = 'Tämän konferenssihuoneen %s samanaikaisten käyttäjien raja on saavutettu. Lisääksesi rajaa ota yhteyttä alustahallintoon.';
$strings['MaxUsersInConferenceRoom'] = 'Enimmäismäärä samanaikaisia käyttäjiä konferenssihuoneessa';
$strings['global_conference_allow_roles'] = 'Globaalin konferenssin linkki näkyvissä vain näille käyttäjärooleille';
$strings['CreatedAt'] = 'Luotu';
$strings['allow_regenerate_recording'] = 'Salli tallenteen uudelleenluonti';
$strings['bbb_force_record_generation'] = 'Pakota tallenteen luonti kokouksen lopussa';
$strings['disable_course_settings'] = 'Poista kurssiasetukset käytöstä';
$strings['UpdateAllCourses'] = 'Päivitä kaikki kurssit';
$strings['UpdateAllCourseSettings'] = 'Päivitä kaikki kurssin asetukset';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Tämä päivittää kerralla kaikki kurssin asetuksesi.';
$strings['ThereIsNoVideoConferenceActive'] = 'Yhtään videokonferenssia ei ole tällä hetkellä aktiivinen';
$strings['RoomClosed'] = 'Huone suljettu';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Kokouksen kesto (minuuteissa)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Salli opiskelijoiden aloittaa konferenssi ryhmissään.';
$strings['hide_conference_link'] = 'Piilota konferenssilinkki kurssityökalusta';
$strings['hide_conference_link_comment'] = 'Näytä tai piilota lohko, jossa on linkki videokonferenssiin liittymispainikkeen vieressä, jotta käyttäjät voivat kopioida sen ja liittää toiseen selainikkunaan tai kutsua muita. Tunnistautuminen on edelleen välttämätöntä ei-julkkisten konferenssien käyttämiseksi.';
$strings['delete_recordings_on_course_delete'] = 'Poista tallenteet, kun kurssi poistetaan';
$strings['defaultVisibilityInCourseHomepage'] = 'Oletusnäkyvyys kurssin etusivulla';
$strings['ViewActivityDashboard'] = 'Näytä toiminnan koontinäyttö';
$strings['Participants'] = 'Osallistujat';
$strings['CountUsers'] = 'Laske käyttäjät';
