<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Oddaljene storitve uporabnika';
$strings['plugin_comment'] = 'Doda povezave, specifične za spletno mesto, z iframe ciljanjem za identifikacijo uporabnika, v vrstico menija.';

$strings['salt'] = 'Sol';
$strings['salt_help'] = 'Skrita niz, uporabljen za generiranje <em>hash</em> parametra URL. Daljši, bolje.
<br/>Oddaljene storitve uporabnika lahko preverijo pristnost ustvarjenega URL z naslednjim PHP izrazom:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Kjer
<br/><code>$salt</code> je ta vhodna vrednost,
<br/><code>$userId</code> je številka uporabnika, na katerega se sklicuje vrednost parametra URL <em>username</em>, in
<br/><code>$hash</code> vsebuje vrednost parametra URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'skrij povezave iz menija';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Dodaj storitev v vrstico menija';
$strings['DeleteServices'] = 'Odstrani storitve iz vrstice menija';
$strings['ServicesToDelete'] = 'Storitve za odstranitev iz vrstice menija';
$strings['ServiceTitle'] = 'Naslov storitve';
$strings['ServiceURL'] = 'Lokacija spletne strani storitve (URL)';
$strings['RedirectAccessURL'] = 'URL za uporabo v Chamilo za preusmeritev uporabnika na storitev (URL)';
$strings['Actions'] = 'Dejanja';
$strings['AddRemoteService'] = 'Dodaj oddaljeno storitev';
$strings['CurrentServices'] = 'Trenutne storitve';
$strings['DeleteService'] = 'Izbriši storitev';
$strings['InvalidSecurityToken'] = 'Neveljaven varnostni žeton.';
$strings['InvalidServiceTitle'] = 'Vnesite naslov storitve.';
$strings['InvalidServiceUrl'] = 'Vnesite veljaven URL HTTP ali HTTPS.';
$strings['MissingSaltWarning'] = 'Konfigurirajte sol pred izpostavitvijo povezav oddaljenih storitev. Sol je potrebna za ustvarjanje podpisanih URL-jev uporabnikov.';
$strings['NoServicesConfigured'] = 'Oddaljene storitve še niso konfigurirane.';
$strings['OpenInIframe'] = 'Odpri v iframe';
$strings['OpenRedirect'] = 'Odpri preusmeritveni URL';
$strings['RemoteServicesDescription'] = 'Upravljajte zunanje storitve, ki prejemajo podpisane URL-je uporabnikov iz Chamila. Te povezave lahko odprejo samo overjeni uporabniki.';
$strings['ServiceCreated'] = 'Oddaljena storitev je bila ustvarjena.';
$strings['ServiceDeleted'] = 'Oddaljena storitev je bila izbrisana.';
$strings['ServiceManagement'] = 'Upravljanje oddaljenih storitev';
$strings['ServiceUnavailable'] = 'Ta oddaljena storitev ni na voljo. Preverite, ali je vtičnik omogočen, sol konfigurirana in URL veljaven.';
