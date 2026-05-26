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
