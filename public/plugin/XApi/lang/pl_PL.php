<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Experience API (xAPI)';
$strings['plugin_comment'] = 'Umożliwia połączenie z zewnętrznym (lub wewnętrznym) magazynem rekordów uczenia się (LRS) oraz korzystanie z aktywności zgodnych ze standardem xAPI.';

$strings['uuid_namespace'] = 'UUID Namespace';
$strings['uuid_namespace_help'] = 'Namespace for universally unique identifiers used as statement IDs.'
    .'<br>This is generated automatically by Chamilo LMS. <strong>Don\'t replace it.</strong>';
$strings['lrs_url'] = 'Punkt końcowy LRS';
$strings['lrs_url_help'] = 'Podstawowy adres URL LRS';
$strings['lrs_auth_username'] = 'Użytkownik LRS';
$strings['lrs_auth_username_help'] = 'Nazwa użytkownika do podstawowego uwierzytelniania HTTP';
$strings['lrs_auth_password'] = 'Hasło LRS';
$strings['lrs_auth_password_help'] = 'Hasło do podstawowego uwierzytelniania HTTP';
$strings['cron_lrs_url'] = 'Cron: punkt końcowy LRS';
$strings['cron_lrs_url_help'] = 'Alternatywny podstawowy adres URL LRS dla procesu cron';
$strings['cron_lrs_auth_username'] = 'Cron: użytkownik LRS';
$strings['cron_lrs_auth_username_help'] = 'Alternatywna nazwa użytkownika do podstawowego uwierzytelniania HTTP dla procesu cron';
$strings['cron_lrs_auth_password'] = 'Cron: hasło LRS';
$strings['cron_lrs_auth_password_help'] = 'Alternatywne hasło do podstawowego uwierzytelniania HTTP dla procesu cron';
$strings['lrs_lp_item_viewed_active'] = 'Wyświetlono element ścieżki nauki';
$strings['lrs_lp_end_active'] = 'Zakończono ścieżkę nauki';
$strings['lrs_quiz_active'] = 'Zakończono test';
$strings['lrs_quiz_question_active'] = 'Odpowiedziano na pytanie testowe';
$strings['lrs_portfolio_active'] = 'Zdarzenia portfolio';

$strings['NoActivities'] = 'Nie dodano jeszcze żadnych aktywności';
$strings['ActivityTitle'] = 'Aktywność';
$strings['AddActivity'] = 'Dodaj aktywność';
$strings['TinCanPackage'] = 'Pakiet TinCan (zip)';
$strings['Cmi5Package'] = 'Pakiet Cmi5 (zip)';
$strings['OnlyZipAllowed'] = 'Dozwolony jest tylko plik ZIP (.zip).';
$strings['ActivityImported'] = 'Zaimportowano aktywność.';
$strings['EditActivity'] = 'Edytuj aktywność';
$strings['ActivityUpdated'] = 'Zaktualizowano aktywność';
$strings['ActivityLaunchUrl'] = 'Adres URL uruchomienia';
$strings['ActivityId'] = 'ID aktywności';
$strings['ActivityType'] = 'Typ aktywności';
$strings['ActivityDeleted'] = 'Usunięto aktywność';
$strings['ActivityLaunch'] = 'Uruchom';
$strings['ActivityFirstLaunch'] = 'Pierwsze uruchomienie';
$strings['ActivityLastLaunch'] = 'Ostatnie uruchomienie';
$strings['LaunchNewAttempt'] = 'Uruchom nową próbę';
$strings['LrsConfiguration'] = 'Konfiguracja LRS';
$strings['Verb'] = 'Czasownik';
$strings['Actor'] = 'Aktor';
$strings['ToolTinCan'] = 'Aktywności';
$strings['Terminated'] = 'Zakończone';
$strings['Completed'] = 'Ukończone';
$strings['Answered'] = 'Odpowiedziane';
$strings['Viewed'] = 'Wyświetlone';
$strings['ActivityAddedToLPCannotBeAccessed'] = 'Ta aktywność została dodana do ścieżki nauki, więc nie można uzyskać do niej bezpośredniego dostępu studentów z tego miejsca.';
$strings['XApiPackage'] = 'Pakiet XApi';
$strings['TinCanAllowMultipleAttempts'] = 'Zezwól na wiele prób';
$strings['defaultVisibilityInCourseHomepage'] = 'Domyślna widoczność na stronie głównej kursu';
