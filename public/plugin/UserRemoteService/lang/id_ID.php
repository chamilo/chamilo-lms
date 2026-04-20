<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Layanan Pengguna Jarak Jauh';
$strings['plugin_comment'] = 'Menambahkan tautan pengenal pengguna yang ditargetkan iframe khusus situs ke bilah menu.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = 'Rangkaian karakter rahasia, digunakan untuk menghasilkan parameter URL <em>hash</em>. Semakin panjang, semakin baik.
<br/>Layanan pengguna jarak jauh dapat memeriksa keaslian URL yang dihasilkan dengan ekspresi PHP berikut :
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Di mana
<br/><code>$salt</code> adalah nilai masukan ini,
<br/><code>$userId</code> adalah nomor pengguna yang dirujuk oleh nilai parameter URL <em>username</em> dan
<br/><code>$hash</code> berisi nilai parameter URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'sembunyikan tautan dari menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Tambahkan layanan ke bilah menu';
$strings['DeleteServices'] = 'Hapus layanan dari bilah menu';
$strings['ServicesToDelete'] = 'Layanan yang akan dihapus dari bilah menu';
$strings['ServiceTitle'] = 'Judul Layanan';
$strings['ServiceURL'] = 'Lokasi situs web layanan (URL)';
$strings['RedirectAccessURL'] = 'URL yang digunakan di Chamilo untuk mengarahkan pengguna ke layanan (URL)';
