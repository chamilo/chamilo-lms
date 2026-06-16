<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Lietotāja attālinātie servisi';
$strings['plugin_comment'] = 'Pievieno vietnes specifiskas iframe mērķētas lietotāju identificējošas saites izvēlnes joslā.';

$strings['salt'] = 'Sāls';
$strings['salt_help'] = 'Noslēpumaina rakstzīmju virkne, ko izmanto <em>hash</em> URL parametra ģenerēšanai. Jo garāka, jo labāk.
<br/>Attālinātie lietotāju servisi var pārbaudīt ģenerētā URL autentiskumu ar šādu PHP izteiksmi:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Kur
<br/><code>$salt</code> ir šī ievades vērtība,
<br/><code>$userId</code> ir lietotāja numurs, uz kuru atsaucas <em>username</em> URL parametra vērtība, un
<br/><code>$hash</code> satur <em>hash</em> URL parametra vērtību.';
$strings['hide_link_from_navigation_menu'] = 'slēpt saites no izvēlnes';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Pievienot servisu izvēlņu joslā';
$strings['DeleteServices'] = 'Noņemt servisus no izvēlņu joslas';
$strings['ServicesToDelete'] = 'Servisi, kas jānoņem no izvēlņu joslas';
$strings['ServiceTitle'] = 'Servisa nosaukums';
$strings['ServiceURL'] = 'Servisa vietnes atrašanās vieta (URL)';
$strings['RedirectAccessURL'] = 'URL, ko izmantot Chamilo, lai novirzītu lietotāju uz servisu (URL)';
$strings['Actions'] = 'Darbības';
$strings['AddRemoteService'] = 'Pievienot attālo pakalpojumu';
$strings['CurrentServices'] = 'Pašreizējie pakalpojumi';
$strings['DeleteService'] = 'Dzēst pakalpojumu';
$strings['InvalidSecurityToken'] = 'Nederīgs drošības marķieris.';
$strings['InvalidServiceTitle'] = 'Lūdzu, ievadiet pakalpojuma nosaukumu.';
$strings['InvalidServiceUrl'] = 'Lūdzu, ievadiet derīgu HTTP vai HTTPS URL.';
$strings['MissingSaltWarning'] = 'Konfigurējiet sāli pirms attālo pakalpojumu saišu publiskošanas. Sāls ir nepieciešams, lai ģenerētu parakstītas lietotāju saites.';
$strings['NoServicesConfigured'] = 'Vēl nav konfigurēts neviens attāls pakalpojums.';
$strings['OpenInIframe'] = 'Atvērt iframe';
$strings['OpenRedirect'] = 'Atvērt novirzīšanas URL';
$strings['RemoteServicesDescription'] = 'Pārvaldīt ārējos pakalpojumus, kas saņem parakstītas lietotāju saites no Chamilo. Tikai autentificēti lietotāji var atvērt šīs saites.';
$strings['ServiceCreated'] = 'Attālais pakalpojums ir izveidots.';
$strings['ServiceDeleted'] = 'Attālais pakalpojums ir dzēsts.';
$strings['ServiceManagement'] = 'Attālo pakalpojumu pārvaldība';
$strings['ServiceUnavailable'] = 'Šis attālais pakalpojums nav pieejams. Pārbaudiet, vai spraudnis ir iespējots, sāls ir konfigurēts un URL ir derīgs.';
