<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\TopLinks\TopLink;

/**
 * Class TopLinksCreateCourseHookObserver.
 */
class TopLinksCreateCourseHookObserver extends HookObserver implements HookCreateCourseObserverInterface
{
    /**
     * XApiCreateCourseHookObserver constructor.
     */
    protected function __construct()
    {
        parent::__construct('plugin/toplinks/src/TopLinksPlugin.php', 'toplinks');
    }

    public function hookCreateCourse(HookCreateCourseEventInterface $hook): int
    {
        $data = $hook->getEventData();

        $type = $data['type'];
        $courseInfo = $data['course_info'];

        $plugin = TopLinksPlugin::create();

        $em = Database::getManager();
        $linkRepo = $em->getRepository(TopLink::class);

        if (HOOK_EVENT_TYPE_POST == $type) {
            foreach ($linkRepo->findAll() as $link) {
                $plugin->addToolInCourse($courseInfo['real_id'], $link);
            }
        }

        return (int) $courseInfo['real_id'];
    }
}
