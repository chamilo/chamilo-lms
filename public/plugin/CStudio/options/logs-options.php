<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/* For Chamilo */
require_once __DIR__.'/../0_dal/dal.global_lib.php';

api_protect_admin_script();

require_once __DIR__.'/../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../0_dal/dal.chamilo_object.php';

require_once __DIR__.'/../0_dal/dal.save.php';

$Collectionlogs = get_collection_oel_tools_logs('admin');

$nblog = 0;

$tablelog = '<table class="form-studiologs" >';
$tablelog .= '<tr>';
$tablelog .= '<th>UserId</th>';
$tablelog .= '<th>Title</th>';
$tablelog .= '<th>Date</th>';
$tablelog .= '</tr>';

foreach ($Collectionlogs as &$rowlogs) {
    $idLog = (int) $rowlogs['id'];
    $user_id = (int) $rowlogs['id_user'];
    $title = $rowlogs['title'];
    $def = $rowlogs['logs'];
    $result = $rowlogs['result'];
    $date_create = $rowlogs['date_create'];
    $lp_id = (int) $rowlogs['id_page'];
    $id_project = (int) $rowlogs['id_project'];
    $createdDateTime = date('Y-m-d H:i:s', $date_create);

    $tablelog .= '<tr>';
    $tablelog .= '<td>'.$user_id.'</td>';
    $tablelog .= '<td>'.$def.'</td>';
    $tablelog .= '<td>'.$createdDateTime.'</td>';
    $tablelog .= '</tr>';

    $nblog++;
}

$tablelog .= '</table>';

echo $tablelog;
