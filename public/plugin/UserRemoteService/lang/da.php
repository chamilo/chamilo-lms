<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Brugerens eksterne tjenester';
$strings['plugin_comment'] = 'Tilføjer side-specifikke iframe-rettede brugeridentificerende links til menulinjen.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = "Hemlig tegnstreng, der bruges til at generere <em>hash</em> URL-parametren. Jo længere, desto bedre.\n<br/>Eksterne bruger-tjenester kan kontrollere den genererede URL's ægthed med følgende PHP-udtryk:\n<br/><code class=\"php\">password_verify(\$salt.\$userId, \$hash)</code>\n<br/>Hvor\n<br/><code>\$salt</code> er denne indtastningsværdi,\n<br/><code>\$userId</code> er brugerens nummer, der henvises til af <em>username</em> URL-parameterværdien, og\n<br/><code>\$hash</code> indeholder <em>hash</em> URL-parameterværdien.";
$strings['hide_link_from_navigation_menu'] = 'skjul links fra menuen';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Tilføj tjeneste til menulinjen';
$strings['DeleteServices'] = 'Fjern tjenester fra menulinjen';
$strings['ServicesToDelete'] = 'Tjenester der skal fjernes fra menulinjen';
$strings['ServiceTitle'] = 'Tjenestens titel';
$strings['ServiceURL'] = 'Tjenestens webstedslokation (URL)';
$strings['RedirectAccessURL'] = 'URL der skal bruges i Chamilo til at omdirigere brugeren til tjenesten (URL)';
