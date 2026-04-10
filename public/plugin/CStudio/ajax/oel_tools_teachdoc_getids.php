<?php

declare(strict_types=1);
/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 18/05/2024
 */

require_once __DIR__.'/../0_dal/dal.global_lib.php';

require_once __DIR__.'/../0_dal/dal.vdatabase.php';

$VDB = new VirtualDatabase();

if (!$VDB->w_api_is_anonymous()) {
    require_once __DIR__.'/../0_dal/dal.save.php';

    require_once 'inc/functions.php';

    $vers = 6;
    $table = 'plugin_oel_tools_teachdoc';

    $UrlWhere = '';
    if ($VDB->w_get_multiple_access_url()) {
        $idurl = $VDB->w_get_current_access_url_id();
        $UrlWhere = " AND id_url = $idurl ";
    }
    $lpIdLst = ',';
    $sqlNS = "SELECT lp_id FROM $table WHERE id_parent = 0 $UrlWhere ";

    $resultPartSub = $VDB->query_to_array($sqlNS);

    foreach ($resultPartSub as $PartTop) {
        $lpId = $PartTop['lp_id'];
        if (0 != $lpId) {
            $lpIdLst = $lpIdLst.$lpId.',';
        }
    }

    $user = $VDB->w_api_get_user_info();

    if (isset($user['status'])) {
        if (SESSIONADMIN == $user['status']
        || COURSEMANAGER == $user['status']
        || PLATFORM_ADMIN == $user['status']) {
            $lpIdLst = ',canedit'.$lpIdLst;
        }
    }

    $_SESSION['teachdocLstIds'] = $lpIdLst;

    echo $lpIdLst;
} else {
    echo 'KO';
}
