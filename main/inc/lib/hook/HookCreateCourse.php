<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookCreateCourse.
 */
class HookCreateCourse extends HookEvent implements HookCreateCourseEventInterface
{
    /**
     * HookCreateCourse constructor.
     *
     * @throws Exception
     */
    protected function __construct()
    {
        parent::__construct('HookCreateCourse');
    }

    /**
     * Update all the observers.
     *
     * @param int $type
     *
     * @return int
     */
    public function notifyCreateCourse($type)
    {
        $this->eventData['type'] = $type;

        /** @var HookCreateCourseObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookCreateCourse($this);
        }

        return 1;
    }
}
