<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc plugin.
 *
 * @version 18/05/2024
 */
if (isset($_POST['id']) || isset($_GET['id'])) {
    require_once '../../0_dal/dal.global_lib.php';

    require_once '../../0_dal/dal.vdatabase.php';
    $VDB = new VirtualDatabase();

    require_once '../inc/functions.php';

    $idPage = get_int_from('id');
    $titleP = get_string_from('title');
    $behavior = get_int_from('behavior');
    $leveldoc = get_int_from('leveldoc');
    $actionP = get_int_from('a');
    $idPageHtmlTop = get_int_from('pt');

    if (false == oel_ctr_rights($idPageHtmlTop)) {
        echo 'KO oel_ctr_rights ';

        exit;
    }

    $user = $VDB->w_api_get_user_info();
    $userId = $user['id'];
    $idUrl = $VDB->w_get_current_access_url_id();

    $table = 'plugin_oel_tools_teachdoc';

    if (666 == $actionP) {
        $params = [
            'type_node' => '-1',
        ];
        $result = $VDB->update($table, $params, ['id = ? AND id_url = ?' => [$idPage, $idUrl]]);

        echo 'OK';
    } else {
        if ('' != $titleP) {
            $params = [
                'title' => $titleP,
                'behavior' => $behavior,
                'leveldoc' => $leveldoc,
            ];
            $result = $VDB->update($table, $params, ['id = ? AND id_url = ?' => [$idPage, $idUrl]]);

            echo 'OK';
        } else {
            echo 'KO';
        }
    }
}
