<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\WhispeakAuth\Controller\AuthenticationController;

require_once __DIR__.'/../../main/inc/global.inc.php';

$controller = new AuthenticationController();

try {
    $controller->index();
} catch (Exception $exception) {
    api_not_allowed(
        true,
        Display::return_message($exception->getMessage(), 'warning')
    );
}
