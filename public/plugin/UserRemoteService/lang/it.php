<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Servizi Remoti Utente';
$strings['plugin_comment'] = "Aggiunge link specifici del sito, mirati a iframe per l'identificazione utente, alla barra del menu.";

$strings['salt'] = 'Salt';
$strings['salt_help'] = "Stringa di caratteri segreta, usata per generare il parametro URL <em>hash</em>. Più lunga è, meglio è.\n<br/>I servizi remoti utente possono verificare l'autenticità dell'URL generato con l'espressione PHP seguente :\n<br/><code class=\"php\">password_verify(\$salt.\$userId, \$hash)</code>\n<br/>Dove\n<br/><code>\$salt</code> è questo valore di input,\n<br/><code>\$userId</code> è il numero dell'utente referenziato dal valore del parametro URL <em>username</em> e\n<br/><code>\$hash</code> contiene il valore del parametro URL <em>hash</em>.";
$strings['hide_link_from_navigation_menu'] = 'nascondi link dal menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Aggiungi servizio alla barra del menu';
$strings['DeleteServices'] = 'Rimuovi servizi dalla barra del menu';
$strings['ServicesToDelete'] = 'Servizi da rimuovere dalla barra del menu';
$strings['ServiceTitle'] = 'Titolo servizio';
$strings['ServiceURL'] = 'Posizione sito web del servizio (URL)';
$strings['RedirectAccessURL'] = "URL da usare in Chamilo per reindirizzare l'utente al servizio (URL)";
