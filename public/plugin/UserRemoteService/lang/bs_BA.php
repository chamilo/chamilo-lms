<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Udaljene usluge korisnika';
$strings['plugin_comment'] = 'Dodaje iframe-ciljana poveznice specifična za stranicu za identifikaciju korisnika u traku s izbornikom.';

$strings['salt'] = 'Sol';
$strings['salt_help'] = 'Tajni niz znakova, korišten za generiranje <em>hash</em> URL parametra. Što duži, to bolje.
<br/>Udaljene usluge korisnika mogu provjeriti autentičnost generiranog URL-a sljedećim PHP izrazom :
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Gdje
<br/><code>$salt</code> je ova ulazna vrijednost,
<br/><code>$userId</code> je broj korisnika na koji se odnosi vrijednost <em>username</em> URL parametra i
<br/><code>$hash</code> sadrži vrijednost <em>hash</em> URL parametra.';
$strings['hide_link_from_navigation_menu'] = 'sakrij poveznice iz izbornika';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Dodaj uslugu u traku s izbornikom';
$strings['DeleteServices'] = 'Ukloni usluge iz trake s izbornikom';
$strings['ServicesToDelete'] = 'Usluge za uklanjanje iz trake s izbornikom';
$strings['ServiceTitle'] = 'Naslov usluge';
$strings['ServiceURL'] = 'Lokacija web stranice usluge (URL)';
$strings['RedirectAccessURL'] = 'URL za upotrebu u Chamilo za preusmjeravanje korisnika na uslugu (URL)';
$strings['Actions'] = 'Akcije';
$strings['AddRemoteService'] = 'Dodaj udaljenu uslugu';
$strings['CurrentServices'] = 'Trenutne usluge';
$strings['DeleteService'] = 'Obriši uslugu';
$strings['InvalidSecurityToken'] = 'Nevažeći sigurnosni token.';
$strings['InvalidServiceTitle'] = 'Molimo unesite naslov usluge.';
$strings['InvalidServiceUrl'] = 'Molimo unesite važeći HTTP ili HTTPS URL.';
$strings['MissingSaltWarning'] = 'Konfigurirajte salt prije izlaganja linkova udaljenih usluga. Salt je neophodan za generisanje potpisanih korisničkih URL-ova.';
$strings['NoServicesConfigured'] = 'Još nisu konfigurirane udaljene usluge.';
$strings['OpenInIframe'] = 'Otvori u iframe-u';
$strings['OpenRedirect'] = 'Otvori URL za preusmjeravanje';
$strings['RemoteServicesDescription'] = 'Upravljajte vanjskim uslugama koje primaju potpisane korisničke URL-ove iz Chamila. Samo autentificirani korisnici mogu otvoriti ove linkove.';
$strings['ServiceCreated'] = 'Udaljena usluga je kreirana.';
$strings['ServiceDeleted'] = 'Udaljena usluga je obrisana.';
$strings['ServiceManagement'] = 'Upravljanje udaljenim uslugama';
$strings['ServiceUnavailable'] = 'Ova udaljena usluga nije dostupna. Provjerite da li je dodatak omogućen, da li je salt konfiguriran i da li je URL važeći.';
