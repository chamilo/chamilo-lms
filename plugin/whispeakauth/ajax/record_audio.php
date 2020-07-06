<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\WhispeakAuth\Controller\AuthenticationRequestController;
use Chamilo\PluginBundle\WhispeakAuth\Controller\CreateEnrollmentRequestController;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$action = isset($_POST['action']) ? $_POST['action'] : 'enrollment';
$isEnrollment = 'enrollment' === $action;
$isAuthentify = 'authentify' === $action;

$isAllowed = false;

if ($isEnrollment) {
    $enrollmentRequest = new CreateEnrollmentRequestController();
    $enrollmentRequest->process();
    die;
}

if ($isAuthentify) {
    $authenticationRequest = new AuthenticationRequestController();
    $authenticationRequest->process();
    die;
}
