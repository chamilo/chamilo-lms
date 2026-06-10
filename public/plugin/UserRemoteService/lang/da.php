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
$strings['Actions'] = 'Handlinger';
$strings['AddRemoteService'] = 'Tilføj fjernservice';
$strings['CurrentServices'] = 'Aktuelle services';
$strings['DeleteService'] = 'Slet service';
$strings['InvalidSecurityToken'] = 'Ugyldig sikkerhedstoken.';
$strings['InvalidServiceTitle'] = 'Indtast venligst en servicetitel.';
$strings['InvalidServiceUrl'] = 'Indtast venligst en gyldig HTTP- eller HTTPS-URL.';
$strings['MissingSaltWarning'] = "Konfigurer en salt, før du eksponerer fjernservice-links. Salten er nødvendig for at generere underskrevne bruger-URL'er.";
$strings['NoServicesConfigured'] = 'Der er ikke konfigureret nogen fjernservices endnu.';
$strings['OpenInIframe'] = 'Åbn i iframe';
$strings['OpenRedirect'] = 'Åbn omdirigerings-URL';
$strings['RemoteServicesDescription'] = "Administrer eksterne services, der modtager underskrevne bruger-URL'er fra Chamilo. Kun godkendte brugere kan åbne disse links.";
$strings['ServiceCreated'] = 'Fjernservicen er blevet oprettet.';
$strings['ServiceDeleted'] = 'Fjernservicen er blevet slettet.';
$strings['ServiceManagement'] = 'Administration af fjernservices';
$strings['ServiceUnavailable'] = "Denne fjernservice er ikke tilgængelig. Kontroller, at plugin'et er aktiveret, at salten er konfigureret, og at URL'en er gyldig.";
