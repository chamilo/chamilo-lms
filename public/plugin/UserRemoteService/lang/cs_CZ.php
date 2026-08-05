<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Vzdálené služby uživatele';
$strings['plugin_comment'] = 'Přidává do lišty menu odkazy specifické pro stránku ve formátu iframe pro identifikaci uživatele.';

$strings['salt'] = 'Sůl';
$strings['salt_help'] = 'Tajný řetězec znaků používaný k vygenerování parametru URL <em>hash</em>. Čím delší, tím lepší.
<br/>Vzdálené služby uživatele mohou ověřit autenticity generovaného URL pomocí následujícího výrazu PHP:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Kde
<br/><code>$salt</code> je tato vstupní hodnota,
<br/><code>$userId</code> je číslo uživatele odkazovaného hodnotou parametru URL <em>username</em> a
<br/><code>$hash</code> obsahuje hodnotu parametru URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'skrýt odkazy z menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Přidat službu do lišty menu';
$strings['DeleteServices'] = 'Odebrat služby z lišty menu';
$strings['ServicesToDelete'] = 'Služby k odebrání z lišty menu';
$strings['ServiceTitle'] = 'Název služby';
$strings['ServiceURL'] = 'Umístění webové stránky služby (URL)';
$strings['RedirectAccessURL'] = 'URL použité v Chamilo k přesměrování uživatele na službu (URL)';
$strings['Actions'] = 'Akce';
$strings['AddRemoteService'] = 'Přidat vzdálenou službu';
$strings['CurrentServices'] = 'Aktuální služby';
$strings['DeleteService'] = 'Smazat službu';
$strings['InvalidSecurityToken'] = 'Neplatný bezpečnostní token.';
$strings['InvalidServiceTitle'] = 'Zadejte název služby.';
$strings['InvalidServiceUrl'] = 'Zadejte platnou adresu HTTP nebo HTTPS.';
$strings['MissingSaltWarning'] = 'Před zpřístupněním odkazů na vzdálené služby nakonfigurujte salt. Salt je vyžadován pro generování podepsaných uživatelských URL.';
$strings['NoServicesConfigured'] = 'Zatím nebyly nakonfigurovány žádné vzdálené služby.';
$strings['OpenInIframe'] = 'Otevřít v iframe';
$strings['OpenRedirect'] = 'Otevřít přesměrovací URL';
$strings['RemoteServicesDescription'] = 'Spravujte externí služby, které přijímají podepsané uživatelské URL z Chamila. Tyto odkazy mohou otevřít pouze přihlášení uživatelé.';
$strings['ServiceCreated'] = 'Vzdálená služba byla vytvořena.';
$strings['ServiceDeleted'] = 'Vzdálená služba byla smazána.';
$strings['ServiceManagement'] = 'Správa vzdálených služeb';
$strings['ServiceUnavailable'] = 'Tato vzdálená služba není dostupná. Zkontrolujte, zda je zásuvný modul povolen, salt je nakonfigurován a URL je platná.';
