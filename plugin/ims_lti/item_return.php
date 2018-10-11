<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(false);
api_block_anonymous_users(false);

if (empty($_POST['content_items']) || empty($_POST['data'])) {
    api_not_allowed(false);
}

$toolId = str_replace('tool:', '', $_POST['data']);

$plugin = ImsLtiPlugin::create();
$em = Database::getManager();
/** @var Course $course */
$course = $em->find('ChamiloCoreBundle:Course', api_get_course_int_id());
/** @var Session|null $session */
$session = $em->find('ChamiloCoreBundle:Session', api_get_session_id());
/** @var ImsLtiTool|null $ltiTool */
$ltiTool = $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', $toolId);

if (!$ltiTool) {
    api_not_allowed(false);
}

$contentItems = json_decode($_POST['content_items'], true);
$contentItems = $contentItems['@graph'];

foreach ($contentItems as $contentItem) {
    /** @var ImsLtiTool $newTool */
    $newTool = clone $ltiTool;

    switch ($contentItem['@type']) {
        case 'LtiLinkItem':
            $newTool
                ->setName(
                    !empty($contentItem['title']) ? $contentItem['title'] : $ltiTool->getName()
                )
                ->setDescription(
                    !empty($contentItem['text']) ? $contentItem['text'] : null
                )
                ->setLaunchUrl(
                    !empty($contentItem['url']) ? $contentItem['url'] : $ltiTool->getLaunchUrl()
                )
                ->setIsGlobal(false)
                ->setActiveDeepLinking(false);

            $em->persist($newTool);
            $em->flush();

            $plugin->addCourseTool($course, $newTool);

            echo Display::return_message($plugin->get_lang('ToolAdded'), 'success');
            break;
    }
}
