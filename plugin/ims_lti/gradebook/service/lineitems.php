<?php
/* For licensing terms, see /license.txt */

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

$course = isset($_REQUEST['c']) ? api_get_course_entity($_REQUEST['c']) : null;
/** @var ImsLtiTool $tool */
$tool = isset($_REQUEST['t']) ? $ltiToolRepo->find($_REQUEST['t']) : null;
$baseTool = null;

if ($tool) {
    $baseTool = $tool->getParent() ?: $tool;
}

$responseHeaders = [];
$responseData = [];
$jsonOptions = 0;

$service = new LtiAssignmentGradesService($tool);

try {
    LtiAssignmentGradesService::validateLineItemsRequest($course, $tool, $httpAccept);

    switch ($requestMethod) {
        case 'POST':
            $requestData = json_decode($phpInput, true);

            $lineItem = $service->createLineItem($requestData);

            $requestData['id'] = $webPluginPath."ims_lti/gradebook/service/lineitem.php?"
                .http_build_query(
                    ['c' => $course->getId(), 'l' => $lineItem->getEvaluation()->getId(), 't' => $tool->getId()]
                );

            $responseData = $requestData;
            $jsonOptions = JSON_UNESCAPED_SLASHES;
            $responseHeaders['Content-Type'] = LtiAssignmentGradesService::TYPE_LINE_ITEM;
            break;
        case 'GET':
        default:
            $resourceLinkId = isset($_GET['resource_link_id']) ? $_GET['resource_link_id'] : '';
            $resourceId = isset($_GET['resource_id']) ? $_GET['resource_id'] : '';
            $tag = isset($_GET['tag']) ? $_GET['tag'] : '';
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 0;
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 0;

            $queryData = [
                'c' => $course->getId(),
                't' => $tool->getId(),
                'resource_link_id' => $resourceLinkId,
                'resource_id' => $resourceId,
                'tag' => $tag,
                'limit' => $limit,
                'page' => $page,
            ];

            $lineItems = $tool->getLineItems($resourceLinkId, $resourceId, $tag, $limit, $page);

            $headerLinks = [];

            if ($limit > 0) {
                $headerLinks['first'] = 1;

                if ($page > 1) {
                    $headerLinks['prev'] = $page - 1;
                }

                if ($page < $lineItems->count() / $limit) {
                    $headerLinks['next'] = $page + 1;
                }

                $headerLinks['last'] = $lineItems->count() / $limit;

                array_walk(
                    $headerLinks,
                    function (&$linkPage, $rel) use ($queryData, $webPluginPath) {
                        $queryData['page'] = $rel;

                        $url = "{$webPluginPath}ims_lti/gradebook/service/lineitems.php?"
                            .http_build_query($queryData);

                        $linkPage = '<'.$url.'>; rel="'.$rel.'"';
                    }
                );

                $responseHeaders['Link'] = implode(', ', $headerLinks);
            }

            $responseData = [];

            /** @var LineItem $lineItem */
            foreach ($lineItems as $lineItem) {
                $responseData[] = $service->getLineItemAsArray($lineItem);
            }

            $responseHeaders['Content-Type'] = LtiAssignmentGradesService::TYPE_LINE_ITEM_CONTAINER;

            header("HTTP/1.0 201 Created");
            break;
    }
} catch (Exception $exception) {
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
