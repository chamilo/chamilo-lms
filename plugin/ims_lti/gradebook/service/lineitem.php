<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\LineItem;

require_once __DIR__.'/../../../../main/inc/global.inc.php';

$httpAccept = $_SERVER['HTTP_ACCEPT'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$phpInput = file_get_contents('php://input');

$webPluginPath = api_get_path(WEB_PLUGIN_PATH);

$em = Database::getManager();
$ltiToolRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');

/** @var Course|null $course */
$course = isset($_REQUEST['c']) ? api_get_course_entity($_REQUEST['c']) : null;
/** @var ImsLtiTool|null $tool */
$tool = isset($_REQUEST['t']) ? $ltiToolRepo->find($_REQUEST['t']) : null;
/** @var LineItem|null $lineItemId */
$lineItem = isset($_REQUEST['l']) ? $em->find('ChamiloPluginBundle:ImsLti\LineItem', $_REQUEST['l']) : null;
$baseTool = null;

if ($tool) {
    $baseTool = $tool->getParent() ?: $tool;
}

$responseHeaders = [];
$responseData = [];
$jsonOptions = 0;

$service = new LtiAssignmentGradesService($tool);

try {
    LtiAssignmentGradesService::validateLineItemRequest($course, $tool, $lineItem, $httpAccept);

    switch ($requestMethod) {
        case 'PUT':
            break;
        case 'DELETE':
            break;
        case 'GET':
        default:
            $responseData = $service->getLineItemAsArray($lineItem);

            $responseHeaders['Content-Type'] = LtiAssignmentGradesService::TYPE_LINE_ITEM;
            break;
    }
} catch (Exception $exception) {
    header("HTTP/1.0 {$exception->getCode()} {$exception->getMessage()}");

    $responseHeaders['Content-Type'] = 'application/json';

    $responseData = [
        'status' => $exception->getCode(),
        'request' => [
            'method' => $requestMethod,
            'url' => $requestUri,
            'accept' => $httpAccept,
        ],
    ];
}

foreach ($responseHeaders as $headerName => $headerValue) {
    header("$headerName: $headerValue");
}

echo json_encode($responseData, $jsonOptions);
