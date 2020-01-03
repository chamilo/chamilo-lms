<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\LineItem;

require_once __DIR__.'/../../../../main/inc/global.inc.php';

var_dump($_SERVER);

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

