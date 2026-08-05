<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Usluge korisnika na daljinu';
$strings['plugin_comment'] = 'Dodaje sajt-specifične iframe-ciljana linkove za identifikaciju korisnika u traku menija.';

$strings['salt'] = 'S alt';
$strings['salt_help'] = 'Tajni niz karaktera, korišćen za generisanje <em>hash</em> URL parametra. Što duži, to bolje.
<br/>Usluge korisnika na daljinu mogu proveriti autentičnost generisanog URL-a sledećim PHP izrazom :
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Gde
<br/><code>$salt</code> je ova uneta vrednost,
<br/><code>$userId</code> je broj korisnika na koji se poziva <em>username</em> URL parametar i
<br/><code>$hash</code> sadrži vrednost <em>hash</em> URL parametra.';
$strings['hide_link_from_navigation_menu'] = 'sakrij linkove iz menija';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Dodaj uslugu u traku menija';
$strings['DeleteServices'] = 'Ukloni usluge iz trake menija';
$strings['ServicesToDelete'] = 'Usluge za uklanjanje iz trake menija';
$strings['ServiceTitle'] = 'Naslov usluge';
$strings['ServiceURL'] = 'Lokacija veb sajta usluge (URL)';
$strings['RedirectAccessURL'] = 'URL za upotrebu u Chamilo-u za preusmeravanje korisnika na uslugu (URL)';
$strings['Actions'] = 'Akcije';
$strings['AddRemoteService'] = 'Dodaj udaljenu uslugu';
$strings['CurrentServices'] = 'Trenutne usluge';
$strings['DeleteService'] = 'Obriši uslugu';
$strings['InvalidSecurityToken'] = 'Nevažeći sigurnosni token.';
$strings['InvalidServiceTitle'] = 'Unesite naslov usluge.';
$strings['InvalidServiceUrl'] = 'Unesite važeći HTTP ili HTTPS URL.';
$strings['MissingSaltWarning'] = 'Konfigurišite salt pre izlaganja linkova udaljenih usluga. Salt je neophodan za generisanje potpisanih URL-ova korisnika.';
$strings['NoServicesConfigured'] = 'Još uvek nisu konfigurisane udaljene usluge.';
$strings['OpenInIframe'] = 'Otvori u iframe-u';
$strings['OpenRedirect'] = 'Otvori URL za preusmeravanje';
$strings['RemoteServicesDescription'] = 'Upravljajte spoljnim uslugama koje primaju potpisane URL-ove korisnika iz Chamila. Samo autentifikovani korisnici mogu da otvore ove linkove.';
$strings['ServiceCreated'] = 'Udaljena usluga je kreirana.';
$strings['ServiceDeleted'] = 'Udaljena usluga je obrisana.';
$strings['ServiceManagement'] = 'Upravljanje udaljenim uslugama';
$strings['ServiceUnavailable'] = 'Ova udaljena usluga nije dostupna. Proverite da li je dodatak omogućen, da li je salt konfigurisán i da li je URL validan.';
