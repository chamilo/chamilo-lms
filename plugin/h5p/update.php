<?php

/* For licensing terms, see /license.txt */
require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$version = isset($_GET['version']) ? Security::remove_XSS($_GET['version']) : '';

$out = '<p>Chamilo H5P is up to date: ';

if ($version == '1-5') {
    $inSql = "ALTER TABLE plugin_h5p ADD terms_d VARCHAR(512) NOT NULL AFTER terms_c;";
    Database::query($inSql);

    $inSql = "ALTER TABLE plugin_h5p ADD terms_e VARCHAR(512) NOT NULL AFTER terms_d;";
    Database::query($inSql);

    $inSql = "ALTER TABLE plugin_h5p ADD terms_f VARCHAR(512) NOT NULL AFTER terms_e;";
    Database::query($inSql);

    $out .= ' version 1-5';
}

$out .= '</p>';
$out .= '<p><a class="btn btn-success" href="list.php" >View list of nodes</a></p>';
$tpl = new Template('Update H5P');
$tpl->assign('form', $out);
$content = $tpl->fetch('/h5p/view/update.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
