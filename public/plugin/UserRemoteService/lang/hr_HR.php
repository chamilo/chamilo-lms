<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Usluge udaljenih korisnika';
$strings['plugin_comment'] = 'Dodaje iframe usmjerene poveznice specifične za stranicu za identifikaciju korisnika u traku s izbornikom.';

$strings['salt'] = 'Sol';
$strings['salt_help'] = 'Tajni niz znakova koji se koristi za generiranje <em>hash</em> URL parametra. Što dulji, to bolje.
<br/>Usluge udaljenih korisnika mogu provjeriti autentičnost generiranog URL-a pomoću sljedećeg PHP izraza :
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Gdje
<br/><code>$salt</code> je ova unesena vrijednost,
<br/><code>$userId</code> je broj korisnika na koji se odnosi vrijednost <em>username</em> URL parametra, a
<br/><code>$hash</code> sadrži vrijednost <em>hash</em> URL parametra.';
$strings['hide_link_from_navigation_menu'] = 'sakrij poveznice iz izbornika';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Dodaj uslugu u traku s izbornikom';
$strings['DeleteServices'] = 'Ukloni usluge iz trake s izbornikom';
$strings['ServicesToDelete'] = 'Usluge za uklanjanje iz trake s izbornikom';
$strings['ServiceTitle'] = 'Naziv usluge';
$strings['ServiceURL'] = 'Lokacija web stranice usluge (URL)';
$strings['RedirectAccessURL'] = 'URL za preusmjeravanje korisnika na uslugu u Chamilo (URL)';
$strings['Actions'] = 'Radnje';
$strings['AddRemoteService'] = 'Dodaj udaljenu uslugu';
$strings['CurrentServices'] = 'Trenutne usluge';
$strings['DeleteService'] = 'Izbriši uslugu';
$strings['InvalidSecurityToken'] = 'Nevažeći sigurnosni token.';
$strings['InvalidServiceTitle'] = 'Unesite naslov usluge.';
$strings['InvalidServiceUrl'] = 'Unesite valjan HTTP ili HTTPS URL.';
$strings['MissingSaltWarning'] = 'Konfigurirajte salt prije izlaganja poveznica udaljenih usluga. Salt je potreban za generiranje potpisanih korisničkih URL-ova.';
$strings['NoServicesConfigured'] = 'Još nisu konfigurirane udaljene usluge.';
$strings['OpenInIframe'] = 'Otvori u iframeu';
$strings['OpenRedirect'] = 'Otvori URL za preusmjeravanje';
$strings['RemoteServicesDescription'] = 'Upravljajte vanjskim uslugama koje primaju potpisane korisničke URL-ove iz Chamila. Samo autentificirani korisnici mogu otvoriti ove poveznice.';
$strings['ServiceCreated'] = 'Udaljena usluga je stvorena.';
$strings['ServiceDeleted'] = 'Udaljena usluga je izbrisana.';
$strings['ServiceManagement'] = 'Upravljanje udaljenim uslugama';
$strings['ServiceUnavailable'] = 'Ova udaljena usluga nije dostupna. Provjerite je li dodatak omogućen, je li salt konfiguriran i je li URL valjan.';
