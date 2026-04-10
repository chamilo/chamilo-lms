<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the OeL plugin.
 *
 * @version 18/05/2024
 */
if (isset($_POST['id']) || isset($_GET['id'])) {
    require_once '../../0_dal/dal.global_lib.php';

    require_once '../../0_dal/dal.vdatabase.php';
    $VDB = new VirtualDatabase();

    require_once '../inc/functions.php';

    require_once '../../0_dal/dal.save.php';

    $idTopPage = get_int_from('id');

    if (false == oel_ctr_rights($idTopPage)) {
        echo 'KO';

        exit;
    }

    $titlenew = get_string_from('title');
    $typeNode = get_int_from('typenode');

    if (-1 != $idTopPage && '' != $titlenew) {
        $user = $VDB->w_api_get_user_info();
        $userId = $user['id'];
        $idUrl = $VDB->w_get_current_access_url_id();

        $MaxOrder = oel_tools_max_order($idTopPage);

        $objectIdI = oel_tools_insert_element($titlenew, $idTopPage, $userId, $MaxOrder, $idUrl, $typeNode);
        echo $objectIdI;
    }
} else {
    echo 'KO';
}
