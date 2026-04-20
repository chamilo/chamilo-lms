<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Perkhidmatan Pengguna Jauh';
$strings['plugin_comment'] = 'Menambah pautan pengenal pasti pengguna yang disasarkan iframe khusus laman ke bar menu.';

$strings['salt'] = 'Garam';
$strings['salt_help'] = 'Rentetan aksara rahsia, digunakan untuk menjana parameter URL <em>hash</em>. Yang paling panjang, yang terbaik.
<br/>Perkhidmatan pengguna jauh boleh menyemak kesahihan URL yang dijana dengan ungkapan PHP berikut:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Di mana
<br/><code>$salt</code> adalah nilai input ini,
<br/><code>$userId</code> adalah nombor pengguna yang dirujuk oleh nilai parameter URL <em>username</em> dan
<br/><code>$hash</code> mengandungi nilai parameter URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'sembunyikan pautan dari menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Tambah perkhidmatan ke bar menu';
$strings['DeleteServices'] = 'Alih keluar perkhidmatan dari bar menu';
$strings['ServicesToDelete'] = 'Perkhidmatan untuk dialih keluar dari bar menu';
$strings['ServiceTitle'] = 'Tajuk perkhidmatan';
$strings['ServiceURL'] = 'Lokasi laman web perkhidmatan (URL)';
$strings['RedirectAccessURL'] = 'URL untuk digunakan dalam Chamilo bagi mengarahkan semula pengguna ke perkhidmatan (URL)';
