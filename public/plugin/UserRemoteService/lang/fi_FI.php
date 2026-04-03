<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Käyttäjän etäpalvelut';
$strings['plugin_comment'] = 'Lisää sivustokohtaisia iframe-kohdistettuja käyttäjää tunnistavia linkkejä valikkopalkkiin.';

$strings['salt'] = 'Suola';
$strings['salt_help'] = 'Salainen merkkijono, jota käytetään <em>hash</em>-URL-parametrin luomiseen. Mitä pidempi, sitä parempi.
<br/>Etäkäyttäjäpalvelut voivat tarkistaa luodun URL:n aitouden seuraavalla PHP-lausekkeella:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Missä
<br/><code>$salt</code> on tämä syötearvo,
<br/><code>$userId</code> on käyttäjän numero, jota viittaa <em>username</em>-URL-parametrin arvo ja
<br/><code>$hash</code> sisältää <em>hash</em>-URL-parametrin arvon.';
$strings['hide_link_from_navigation_menu'] = 'piilota linkit valikosta';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Lisää palvelu valikkopalkkiin';
$strings['DeleteServices'] = 'Poista palvelut valikkopalkista';
$strings['ServicesToDelete'] = 'Valikkopalkista poistettavat palvelut';
$strings['ServiceTitle'] = 'Palvelun otsikko';
$strings['ServiceURL'] = 'Palvelun verkkosivuston sijainti (URL)';
$strings['RedirectAccessURL'] = 'Chamiloissa käytettävä URL, jolla ohjataan käyttäjä palveluun (URL)';
