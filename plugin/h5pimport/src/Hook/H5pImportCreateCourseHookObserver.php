<?php

/* For licensing terms, see /license.txt */

class H5pImportCreateCourseHookObserver extends HookObserver implements HookCreateCourseObserverInterface
{
    /**
     * H5pImportCreatecourseHookObserver constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            'plugin/h5pimport/H5pImportPlugin.php',
            'h5pimport'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function hookCreateCourse(HookCreateCourseEventInterface $hook)
    {
        $data = $hook->getEventData();

        $type = $data['type'];
        $courseInfo = $data['course_info'];

        $plugin = H5pImportPlugin::create();

        if (HOOK_EVENT_TYPE_POST == $type) {
            $plugin->addCourseTool($courseInfo['real_id']);
        }
    }
}
