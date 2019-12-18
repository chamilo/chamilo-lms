<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookCreateCourseEventInterface;
use Chamilo\CoreBundle\Hook\Interfaces\HookCreateCourseObserverInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class HookCreateCourse.
 */
class HookCreateCourse extends HookEvent implements HookCreateCourseEventInterface
{
    /**
     * HookCreateCourse constructor.
     */
    protected function __construct(EntityManager $entityManager)
    {
        parent::__construct('HookCreateCourse', $entityManager);
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
