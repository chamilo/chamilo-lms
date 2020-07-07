<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\WhispeakAuth\Controller\AuthenticationRequestController;
use Chamilo\PluginBundle\WhispeakAuth\Controller\EnrollmentController;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$action = isset($_POST['action']) ? $_POST['action'] : 'enrollment';
$isEnrollment = 'enrollment' === $action;
$isAuthentify = 'authentify' === $action;

$isAllowed = false;

if ($isEnrollment) {
    api_block_anonymous_users(false);

    $controller = new EnrollmentController();

    try {
        $controller->ajax();
    } catch (Exception $exception) {
        WhispeakAuthPlugin::displayNotAllowedMessage(
            $exception->getMessage()
        );
    }
    die;
}

if ($isAuthentify) {
    $authenticationRequest = new AuthenticationRequestController();
    $authenticationRequest->process();
    die;
}
