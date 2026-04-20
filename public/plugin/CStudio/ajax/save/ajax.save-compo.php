<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the OeL plugin.
 *
 * @version 18/05/2024
 */
if (isset($_POST['id']) || isset($_GET['id'])) {
    if (
        (isset($_POST['GpsComps'], $_POST['GpsStyle']))
        || (isset($_GET['GpsComps'], $_GET['GpsStyle']))
    ) {
        require_once '../../0_dal/dal.global_lib.php';

        require_once '../../0_dal/dal.vdatabase.php';
        $VDB = new VirtualDatabase();

        require_once '../inc/functions.php';

        require_once '../../0_dal/dal.save.php';

        $idPage = get_int_from('id');

        if (false == oel_ctr_rights($idPage)) {
            echo 'KO';

            exit;
        }

        $GpsComps = get_string_direct_from('GpsComps');
        $GpsStyle = get_string_direct_from('GpsStyle');

        if ('' != $GpsComps && '' != $GpsStyle) {
            oel_tools_update_element_compo($GpsComps, $GpsStyle, $idPage);
        } else {
            echo ' Saved KO';
        }
    }
} else {
    echo 'error no id';
}
