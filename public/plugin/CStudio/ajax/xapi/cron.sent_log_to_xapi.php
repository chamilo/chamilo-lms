<?php

declare(strict_types=1);

require_once __DIR__.'/../../0_dal/dal.global_lib.php';

require_once __DIR__.'/../../0_dal/dal.chamilo_object.php';

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../../0_dal/dal.save.php';

require_once __DIR__.'/../xapi/sent_to_shared_statement.php';

require_once __DIR__.'/../inc/functions.php';

$Collectionlogs = get_collection_oel_tools_logs();

$varlog = '';
$nblog = 0;

// Caution MODE TEST !!
$modeTest = true;

foreach ($Collectionlogs as &$rowlogs) {
    if ($nblog < 500 || false == $modeTest) {
        $idLog = (int) $rowlogs['id'];
        $user_id = (int) $rowlogs['id_user'];
        $title = $rowlogs['title'];
        $def = $rowlogs['logs'];
        $result = $rowlogs['result'];
        $date_create = $rowlogs['date_create'];

        // Lp_Id Ref
        $lp_id = (int) $rowlogs['id_page'];

        // Studio project id Ref
        $id_project = (int) $rowlogs['id_project'];
        $verbXapi = 'interacted';

        $isexercicestatement = false;

        $response = '';
        if (false != strpos('@'.$title, 'quizz_')) {
            $isexercicestatement = true;
            $varlog .= "<span style='color:purple;font-size:17px;' >Quizz Object !</span><br>";
            if (false != strpos('@'.$def, '|')) {
                $optD = explode('|', $def);
                $def = ''.$optD[0];
                $response = ''.$optD[1];
                $varlog .= "<span style='color:purple;font-size:17px;' >response : $response</span><br>";
            }
        }

        if (false != strpos('@'.$title, 'h5p_')) {
            $isexercicestatement = true;
            $varlog .= "<span style='color:purple;font-size:17px;' >H5P Object !</span><br>";
        }

        if ($isexercicestatement) {
            $verbXapi = 'answered';
        }
        if (false != strpos('@'.$title, 'finish')) {
            $verbXapi = 'completed';
        }
        if (false != strpos('@'.$title, 'launch')) {
            $verbXapi = 'attempted';
        }

        $varlog .= "<span style='color:blue;font-size:19px;' > sendLogStudioToSharedStatement($user_id,'$title','$def',$result,'$response',$date_create,$modeTest)</span><br>";
        $varlog .= sendLogStudioToSharedStatement($user_id, $verbXapi, $title, $def, $result, $response, $date_create, $modeTest);

        if (false == $modeTest) {
            update_collection_oel_tools_logs($idLog);
        }

        $nblog++;
    }
}

echo $varlog;
