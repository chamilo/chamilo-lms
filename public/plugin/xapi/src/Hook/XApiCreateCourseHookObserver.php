<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

class XApiCreateCourseHookObserver extends HookObserver implements HookCreateCourseObserverInterface
{
    protected function __construct()
    {
        parent::__construct(
            'plugin/xapi/src/XApiPlugin.php',
            'xapi'
        );
    }

    public function hookCreateCourse(HookCreateCourseEventInterface $hook): void
    {
        $data = $hook->getEventData();

        $type = $data['type'];
        $courseInfo = $data['course_info'];

        $plugin = XApiPlugin::create();

        if (HOOK_EVENT_TYPE_POST == $type) {
            $plugin->addCourseToolForTinCan($courseInfo['real_id']);
        }
    }
}
