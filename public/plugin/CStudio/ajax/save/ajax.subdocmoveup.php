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

    require_once '../../0_dal/dal.save.php';

    $idPage = get_int_from('id');
    $action = get_int_from('a');

    $user = $VDB->w_api_get_user_info();
    $userId = $user['id'];
    $idUrl = api_get_current_access_url_id();

    $topPage = get_top_page_id($idPage);

    if (false == oel_ctr_rights($topPage)) {
        echo 'KO';

        exit;
    }

    range_all_pages($topPage, $idPage, $action, $idUrl);
}
