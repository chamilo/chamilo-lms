<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Usługi zdalne użytkownika';
$strings['plugin_comment'] = 'Dodaje do paska menu specyficzne dla witryny linki iframe skierowane do identyfikacji użytkownika.';

$strings['salt'] = 'Sól';
$strings['salt_help'] = 'Sekretny ciąg znaków, używany do generowania parametru URL <em>hash</em>. Im dłuższy, tym lepszy.
<br/>Usługi zdalne użytkownika mogą sprawdzić autentyczność wygenerowanego URL za pomocą następującego wyrażenia PHP:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Gdzie
<br/><code>$salt</code> to ta wartość wejściowa,
<br/><code>$userId</code> to numer użytkownika wskazanego przez wartość parametru URL <em>username</em>, a
<br/><code>$hash</code> zawiera wartość parametru URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'ukryj linki z menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Dodaj usługę do paska menu';
$strings['DeleteServices'] = 'Usuń usługi z paska menu';
$strings['ServicesToDelete'] = 'Usługi do usunięcia z paska menu';
$strings['ServiceTitle'] = 'Tytuł usługi';
$strings['ServiceURL'] = 'Lokalizacja witryny internetowej usługi (URL)';
$strings['RedirectAccessURL'] = 'URL używany w Chamilo do przekierowania użytkownika do usługi (URL)';
$strings['Actions'] = 'Akcje';
$strings['AddRemoteService'] = 'Dodaj usługę zdalną';
$strings['CurrentServices'] = 'Bieżące usługi';
$strings['DeleteService'] = 'Usuń usługę';
$strings['InvalidSecurityToken'] = 'Nieprawidłowy token bezpieczeństwa.';
$strings['InvalidServiceTitle'] = 'Proszę podać tytuł usługi.';
$strings['InvalidServiceUrl'] = 'Proszę podać prawidłowy adres URL HTTP lub HTTPS.';
$strings['MissingSaltWarning'] = 'Skonfiguruj salt przed udostępnianiem linków do usług zdalnych. Salt jest wymagany do generowania podpisanych adresów URL użytkowników.';
$strings['NoServicesConfigured'] = 'Nie skonfigurowano jeszcze żadnych usług zdalnych.';
$strings['OpenInIframe'] = 'Otwórz w iframe';
$strings['OpenRedirect'] = 'Otwórz przekierowanie URL';
$strings['RemoteServicesDescription'] = 'Zarządzaj usługami zewnętrznymi, które otrzymują podpisane adresy URL użytkowników z Chamilo. Tylko uwierzytelnieni użytkownicy mogą otwierać te linki.';
$strings['ServiceCreated'] = 'Usługa zdalna została utworzona.';
$strings['ServiceDeleted'] = 'Usługa zdalna została usunięta.';
$strings['ServiceManagement'] = 'Zarządzanie usługami zdalnymi';
$strings['ServiceUnavailable'] = 'Ta usługa zdalna jest niedostępna. Sprawdź, czy wtyczka jest włączona, salt jest skonfigurowany, a adres URL jest prawidłowy.';
