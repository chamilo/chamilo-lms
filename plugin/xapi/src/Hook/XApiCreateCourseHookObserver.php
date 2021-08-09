<?php

/* For licensing terms, see /license.txt */

/**
 * Class XApiCreateCourseHookObserver.
 */
class XApiCreateCourseHookObserver extends HookObserver implements HookCreateCourseObserverInterface
{
    /**
     * XApiCreateCourseHookObserver constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            'plugin/xapi/src/XApiPlugin.php',
            'xapi'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hookCreateCourse(HookCreateCourseEventInterface $hook)
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
