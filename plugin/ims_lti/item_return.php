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
$ltiToolRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');
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
    switch ($contentItem['@type']) {
        case 'LtiLinkItem':
            $url = empty($contentItem['url']) ? $ltiTool->getLaunchUrl() : $contentItem['url'];

            /** @var ImsLtiTool $newLtiTool */
            $newLtiTool = $ltiToolRepo->findOneBy(['launchUrl' => $url, 'isGlobal' => false]);

            if (empty($newLtiTool)) {
                $newLtiTool = new ImsLtiTool();
                $newLtiTool
                    ->setLaunchUrl($url)
                    ->setConsumerKey(
                        $ltiTool->getConsumerKey()
                    )
                    ->setSharedSecret(
                        $ltiTool->getSharedSecret()
                    );
            }

            $newLtiTool
                ->setName(
                    !empty($contentItem['title']) ? $contentItem['title'] : $ltiTool->getName()
                )
                ->setDescription(
                    !empty($contentItem['text']) ? $contentItem['text'] : null
                );

            $em->persist($newLtiTool);
            $em->flush();

            $courseTool = $plugin->findCourseToolByLink($course, $newLtiTool);

            if ($courseTool) {
                $plugin->updateCourseTool($courseTool, $newLtiTool);
            } else {
                $plugin->addCourseTool($course, $newLtiTool);
            }

            echo Display::return_message($plugin->get_lang('ToolAdded'), 'success');
            break;
    }
}
