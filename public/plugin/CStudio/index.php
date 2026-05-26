<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

$request = Container::getRequest();
$requestRoute = $request->query->get('_route');
$requestRouteName = $request->query->get('_route_name');

// lp_controller.php
$posCtr = '/main/lp/lp_controller.php' === $requestRoute;
$posGdr = 'LpList' === $requestRouteName;
$versionLudi = '5';
$fhL = '';

if (!$posCtr && !$posGdr) {
} else {
    require_once '0_dal/dal.vdatabase.php';
    $VDB = new VirtualDatabase();

    $user = api_get_user_info();

    if (isset($user['id'])) {
        $pwpIcoLudi = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/resources/js/teachdoc-icon.js';
        $fhL .= '<script src="'.$pwpIcoLudi.'?v='.$versionLudi.'"></script>';

        if (isset($_SESSION['teachdocLstIds'])) {
            $fhL .= '<div id="teachdocLstIds" style="display:none;" >'.htmlspecialchars((string) $_SESSION['teachdocLstIds'], \ENT_QUOTES, 'UTF-8').'</div>';
        } else {
            $fhL .= '<div id="teachdocLstIds" style="display:none;" ></div>';
        }
    }
}

echo $fhL;
