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
