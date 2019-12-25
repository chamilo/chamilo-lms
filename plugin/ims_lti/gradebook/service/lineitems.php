<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../../../main/inc/global.inc.php';

$httpAccept = $_SERVER['HTTP_ACCEPT'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$rawContent = file_get_contents('php://input');

$cId = isset($_REQUEST['c']) ? (int) $_REQUEST['c'] : 0;
$evalId = isset($_REQUEST['l']) ? (int) $_REQUEST['l'] : 0;
$baseToolId = isset($_REQUEST['t']) ? (int) $_REQUEST['t'] : 0;

$em = Database::getManager();

$course = api_get_course_entity($cId);

if (!$course) {
    exit();
}

$evaluation = $em
    ->getRepository('ChamiloCoreBundle:GradebookEvaluation')
    ->findOneBy(['id' => $evalId, 'courseCode' => $course->getCode()]);

$ltiToolRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');
/** @var ImsLtiTool $tool */
$baseTool = $ltiToolRepo->find($baseToolId);

if (!$baseTool) {
    exit();
}

$tool = $ltiToolRepo->findOneBy(['parent' => $baseTool, 'course' => $course]);

if (!$tool) {
    $tool = $ltiToolRepo->findOneBy(['course' => $course, 'id' => $baseTool->getId()]);
}

if (!$tool) {
    exit();
}

$result = [];

if ('application/vnd.ims.lis.v2.lineitemcontainer+json' === $httpAccept) {
    switch ($requestMethod) {
        case 'GET':
            $lineItem = getLineItem($tool);

            $result = [$lineItem];
            break;
    }
} elseif ('application/vnd.ims.lis.v2.lineitem+json' === $httpAccept) {
    switch ($requestMethod) {
        case 'POST':
            $requestData = json_decode($rawContent, true);

            $lineItem = postLineItem($tool, $requestData['scoreMaximum'], $requestData['label']);
            $result = $lineItem;
            break;
    }
}

header("Content-Type: $httpAccept");
echo json_encode($result);

/**
 * @param ImsLtiTool $tool
 *
 * @return array
 */
function getLineItem(ImsLtiTool $tool)
{
    $evaluation = $tool->getGradebookEval();

    if (!$evaluation) {
        return [];
    }

    $webPluginPath = api_get_path(WEB_PLUGIN_PATH);

    return [
        'id' => "{$webPluginPath}ims_lti/gradebook/service/lineitems.php?"
            .http_build_query(
                [
                    'c' => $tool->getCourse(),
                    'l' => $evaluation->getId(),
                    't' => $tool->getId(),
                ]
            ),
        'scoreMaximum' => $evaluation->getMax(),
        'label' => $evaluation->getName(),
        'resourceId' => "eval-{$evaluation->getId()}",
        'tag' => 'evaluation',
    ];
}

function postLineItem(ImsLtiTool $tool, $max, $label, $resourceId = '', $tag = '')
{
    $categories = Category::load(null, null, $tool->getCourse()->getCode());
    $gradebookCategory = $categories[0];

    $weight = $gradebookCategory->getRemainingWeight();

    $em = Database::getManager();

    $userId = api_get_user_id();
    $courseCode = api_get_course_id();

    $evaluation = new Evaluation();
    $evaluation->set_user_id($userId);
    $evaluation->set_category_id($gradebookCategory->get_id());
    $evaluation->set_course_code($courseCode);
    $evaluation->set_name($label);
    $evaluation->set_description(null);
    $evaluation->set_weight($weight);
    $evaluation->set_max($max);
    $evaluation->set_visible(1);
    $evaluation->add();

    $gradebookEvaluation = $em->find('ChamiloCoreBundle:GradebookEvaluation', $evaluation->get_id());

    $tool->setGradebookEval($gradebookEvaluation);

    $em->persist($tool);
    $em->flush();

    return getLineItem($tool);
}
