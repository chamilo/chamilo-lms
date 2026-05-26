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
