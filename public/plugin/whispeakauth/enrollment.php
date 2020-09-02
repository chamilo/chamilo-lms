<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\WhispeakAuth\Controller\EnrollmentController;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users(true);

$controller = new EnrollmentController();

try {
    $controller->index();
} catch (Exception $exception) {
    api_not_allowed(
        true,
        Display::return_message($exception->getMessage(), 'warning')
    );
}
