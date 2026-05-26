<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Kullanıcı Uzaktan Servisleri';
$strings['plugin_comment'] = 'Menü çubuğuna siteye özgü iframe hedefli kullanıcı tanımlayıcı bağlantılar ekler.';

$strings['salt'] = 'Tuz';
$strings['salt_help'] = "Gizli karakter dizisi, <em>hash</em> URL parametresini üretmek için kullanılır. Ne kadar uzun, o kadar iyi.\n<br/>Uzaktan kullanıcı servisleri şu PHP ifadesiyle üretilen URL'nin doğruluğunu kontrol edebilir:\n<br/><code class=\"php\">password_verify(\$salt.\$userId, \$hash)</code>\n<br/>Burada\n<br/><code>\$salt</code> bu giriş değeri,\n<br/><code>\$userId</code> <em>kullanıcı adı</em> URL parametresi değeriyle referans verilen kullanıcının numarası ve\n<br/><code>\$hash</code> <em>hash</em> URL parametresi değerini içerir.";
$strings['hide_link_from_navigation_menu'] = 'menüden bağlantıları gizle';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Servisi menü çubuğuna ekle';
$strings['DeleteServices'] = 'Menü çubuğundan servisleri kaldır';
$strings['ServicesToDelete'] = 'Menü çubuğundan kaldırılacak servisler';
$strings['ServiceTitle'] = 'Servis başlığı';
$strings['ServiceURL'] = 'Servis web sitesi konumu (URL)';
$strings['RedirectAccessURL'] = "Kullanıcıyı servise yönlendirmek için Chamilo'da kullanılacak URL (URL)";
