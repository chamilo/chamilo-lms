<?php

require_once '../../../main/inc/global.inc.php';

if (isset($_REQUEST['key']) && isset($_REQUEST['username'])) {
    $securityKey = api_get_configuration_value('security_key');
    $result = api_is_valid_secret_key($_REQUEST['key'], $securityKey);
    if ($result) {
        $userInfo = api_get_user_info_from_username($_REQUEST['username']);
        if ($userInfo) {
            $result = Tracking::getCourseLpProgress($userInfo['id'], 0);
            echo json_encode($result);
        }
    }
}
