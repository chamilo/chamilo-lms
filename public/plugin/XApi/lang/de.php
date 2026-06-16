<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Experience API (xAPI)';
$strings['plugin_comment'] = 'Ermöglicht die Verbindung zu einem externen (oder internen) Learning Record Store und die Nutzung von Aktivitäten, die mit dem xAPI-Standard kompatibel sind.';

$strings['uuid_namespace'] = 'UUID Namespace';
$strings['uuid_namespace_help'] = 'Namespace for universally unique identifiers used as statement IDs.'
    .'<br>This is generated automatically by Chamilo LMS. <strong>Don\'t replace it.</strong>';
$strings['lrs_url'] = 'LRS-Endpunkt';
$strings['lrs_url_help'] = 'Basis-URL des LRS';
$strings['lrs_auth_username'] = 'LRS-Benutzer';
$strings['lrs_auth_username_help'] = 'Benutzername für die HTTP-Basisauthentifizierung';
$strings['lrs_auth_password'] = 'LRS-Passwort';
$strings['lrs_auth_password_help'] = 'Passwort für die HTTP-Basisauthentifizierung';
$strings['cron_lrs_url'] = 'Cron: LRS-Endpunkt';
$strings['cron_lrs_url_help'] = 'Alternative Basis-URL des LRS für den Cron-Prozess';
$strings['cron_lrs_auth_username'] = 'Cron: LRS-Benutzer';
$strings['cron_lrs_auth_username_help'] = 'Alternativer Benutzername für die HTTP-Basisauthentifizierung für den Cron-Prozess';
$strings['cron_lrs_auth_password'] = 'Cron: LRS-Passwort';
$strings['cron_lrs_auth_password_help'] = 'Alternatives Passwort für die HTTP-Basisauthentifizierung für den Cron-Prozess';
$strings['lrs_lp_item_viewed_active'] = 'Lernpfad-Element angesehen';
$strings['lrs_lp_end_active'] = 'Lernpfad beendet';
$strings['lrs_quiz_active'] = 'Test beendet';
$strings['lrs_quiz_question_active'] = 'Testfrage beantwortet';
$strings['lrs_portfolio_active'] = 'Portfolio-Ereignisse';

$strings['NoActivities'] = 'Noch keine Aktivitäten hinzugefügt';
$strings['ActivityTitle'] = 'Aktivität';
$strings['AddActivity'] = 'Aktivität hinzufügen';
$strings['TinCanPackage'] = 'TinCan-Paket (zip)';
$strings['Cmi5Package'] = 'Cmi5-Paket (zip)';
$strings['OnlyZipAllowed'] = 'Nur ZIP-Dateien erlaubt (.zip).';
$strings['ActivityImported'] = 'Aktivität importiert.';
$strings['EditActivity'] = 'Aktivität bearbeiten';
$strings['ActivityUpdated'] = 'Aktivität aktualisiert';
$strings['ActivityLaunchUrl'] = 'Start-URL';
$strings['ActivityId'] = 'Aktivitäts-ID';
$strings['ActivityType'] = 'Aktivitätstyp';
$strings['ActivityDeleted'] = 'Aktivität gelöscht';
$strings['ActivityLaunch'] = 'Starten';
$strings['ActivityFirstLaunch'] = 'Erster Start am';
$strings['ActivityLastLaunch'] = 'Letzter Start am';
$strings['LaunchNewAttempt'] = 'Neuen Versuch starten';
$strings['LrsConfiguration'] = 'LRS-Konfiguration';
$strings['Verb'] = 'Verb';
$strings['Actor'] = 'Akteur';
$strings['ToolTinCan'] = 'Aktivitäten';
$strings['Terminated'] = 'Beendet';
$strings['Completed'] = 'Abgeschlossen';
$strings['Answered'] = 'Beantwortet';
$strings['Viewed'] = 'Angesehen';
$strings['ActivityAddedToLPCannotBeAccessed'] = 'Diese Aktivität ist in einen Lernpfad eingebunden und kann von Studierenden nicht direkt von hier aus aufgerufen werden.';
$strings['XApiPackage'] = 'XApi-Paket';
$strings['TinCanAllowMultipleAttempts'] = 'Mehrere Versuche zulassen';
$strings['defaultVisibilityInCourseHomepage'] = 'Standard-Sichtbarkeit auf der Kurs-Startseite';
