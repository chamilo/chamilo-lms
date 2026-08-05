<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Användartjänster på distans';
$strings['plugin_comment'] = 'Lägger till webbplats-specifika iframe-målade användaridentifierande länkar till menyraden.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = 'Hemlig teckensträng, används för att generera <em>hash</em> URL-parametern. Ju längre, desto bättre.
<br/>Fjdistans-användartjänster kan kontrollera den genererade URL:ens äkthet med följande PHP-uttryck:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Där
<br/><code>$salt</code> är detta inmatade värde,
<br/><code>$userId</code> är numret på användaren som refereras av <em>username</em> URL-parameter-värdet och
<br/><code>$hash</code> innehåller <em>hash</em> URL-parameter-värdet.';
$strings['hide_link_from_navigation_menu'] = 'dölj länkar från menyn';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Lägg till tjänst i menyraden';
$strings['DeleteServices'] = 'Ta bort tjänster från menyraden';
$strings['ServicesToDelete'] = 'Tjänster att ta bort från menyraden';
$strings['ServiceTitle'] = 'Tjänsttitel';
$strings['ServiceURL'] = 'Tjänstens webbplatsadress (URL)';
$strings['RedirectAccessURL'] = 'URL att använda i Chamilo för att omdirigera användaren till tjänsten (URL)';
$strings['Actions'] = 'Åtgärder';
$strings['AddRemoteService'] = 'Lägg till fjärrtjänst';
$strings['CurrentServices'] = 'Aktuella tjänster';
$strings['DeleteService'] = 'Ta bort tjänst';
$strings['InvalidSecurityToken'] = 'Ogiltig säkerhetstoken.';
$strings['InvalidServiceTitle'] = 'Ange en tjänsttitel.';
$strings['InvalidServiceUrl'] = 'Ange en giltig HTTP- eller HTTPS-URL.';
$strings['MissingSaltWarning'] = 'Konfigurera en salt innan du exponerar länkar till fjärrtjänster. Salten krävs för att generera signerade användar-URL:er.';
$strings['NoServicesConfigured'] = 'Inga fjärrtjänster har konfigurerats ännu.';
$strings['OpenInIframe'] = 'Öppna i iframe';
$strings['OpenRedirect'] = 'Öppna omdirigerings-URL';
$strings['RemoteServicesDescription'] = 'Hantera externa tjänster som tar emot signerade användar-URL:er från Chamilo. Endast autentiserade användare kan öppna dessa länkar.';
$strings['ServiceCreated'] = 'Fjärrtjänsten har skapats.';
$strings['ServiceDeleted'] = 'Fjärrtjänsten har tagits bort.';
$strings['ServiceManagement'] = 'Hantering av fjärrtjänster';
$strings['ServiceUnavailable'] = 'Denna fjärrtjänst är inte tillgänglig. Kontrollera att pluginen är aktiverad, att salten är konfigurerad och att URL:en är giltig.';
