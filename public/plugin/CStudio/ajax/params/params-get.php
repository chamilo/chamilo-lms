<?php

declare(strict_types=1);
/**
 * This file contains the functions used by the OeL plugin.
 *
 * @version 18/05/2024
 */

require_once '../../0_dal/dal.global_lib.php';

require_once '../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../inc/functions.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

if (!isset($_GET['idteach'])) {
    exit;
}

$step = isset($_GET['step']) ? (int) $_GET['step'] : 0;
$idPageTop = get_int_from('idteach');

if (false == oel_ctr_rights($idPageTop)) {
    echo 'KO';

    exit;
}

$table = 'plugin_oel_tools_teachdoc';

if (0 == $step) {
    $sql = 'SELECT options FROM plugin_oel_tools_teachdoc ';
    $sql .= 'WHERE id = '.$idPageTop;
    $options = $VDB->get_value_by_query($sql, 'options');
    echo $options;
}

if (1 == $step) {
    $opt = get_string_from('opt');
    $VDB->update($table, ['options' => $opt], ['id = ?' => $idPageTop]);
}

if (4 == $step) {
    $idPage = get_int_from('idpg');

    if (isset($_GET['opt']) && '' !== $_GET['opt']) {
        $opt = get_string_from('opt');
        $VDB->update($table, ['options' => $opt], ['id = ? AND id_parent = ?' => [$idPage, $idPageTop]]);
    }
}
