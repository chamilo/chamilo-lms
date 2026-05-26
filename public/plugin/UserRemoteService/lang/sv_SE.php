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
