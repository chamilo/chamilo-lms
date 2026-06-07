<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

$request = Container::getRequest();

$requestRoute = (string) ($request->query->get('_route') ?? $request->attributes->get('_route') ?? '');
$requestRouteName = (string) ($request->query->get('_route_name') ?? $request->attributes->get('_route') ?? '');
$requestPath = $request->getPathInfo();

$normalizedRoute = rtrim($requestRoute, '/');
$normalizedPath = rtrim($requestPath, '/');

// Legacy learning path controller.
$posCtr = '/main/lp/lp_controller.php' === $requestRoute
    || str_contains($requestRoute, '/main/lp/lp_controller.php')
    || str_contains($requestPath, '/main/lp/lp_controller.php');

// Vue learning path list route name sent by the plugin-region frontend.
$posGdr = 'LpList' === $requestRouteName;

// Modern Vue learning path list route, for example /resources/lp/{nodeId}.
$posModernLpList = 1 === preg_match('#^/resources/lp/[0-9]+$#', $normalizedRoute)
    || 1 === preg_match('#^/resources/lp/[0-9]+$#', $normalizedPath);

$versionLudi = '11';
$fhL = '';

if ($posCtr || $posGdr || $posModernLpList) {
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
