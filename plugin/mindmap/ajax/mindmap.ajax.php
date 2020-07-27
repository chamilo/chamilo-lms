<?php

/* For licensing terms, see /license.txt */

/**
 * Process the saving of the mindmap updates (when clicking the logo to get out).
 */
if (isset($_GET['id'])) {
    if (isset($_POST['datamap'])) {
        require_once __DIR__.'/../../../main/inc/global.inc.php';

        if (api_is_anonymous()) {
            echo "KO";
            exit;
        }

        $idMM = -1;
        if (isset($_GET['id'])) {
            $idMM = (int) $_GET['id'];
        }

        $dataMap = '';
        if (isset($_POST['datamap'])) {
            $dataMap = $_POST['datamap'];
        }

        if ($dataMap != '') {
            $user = api_get_user_info();

            $table = 'plugin_mindmap';
            $params = [
                'mindmap_data' => $dataMap,
            ];
            $whereConditions = [
                'id = ?' => $idMM,
                'AND (user_id = ?' => $user['id'],
                'OR is_shared = 1)',
            ];

            $isAdmin = api_is_platform_admin();

            if ($user['status'] == SESSIONADMIN || $user['status'] == PLATFORM_ADMIN || $isAdmin) {
                $whereConditions = [
                    'id = ?' => $idMM,
                ];
            }

            Database::update($table, $params, $whereConditions);

            echo 'OK';
        } else {
            echo 'KO';
        }
    } else {
        echo 'KO';
    }
} else {
    echo 'KO';
}
