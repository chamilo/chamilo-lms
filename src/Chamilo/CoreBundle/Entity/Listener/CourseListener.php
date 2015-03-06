<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CourseBundle\ToolChain;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class CourseListener
 * Course entity listener, when a course is created the tool chain is loaded.
 * @package Chamilo\CoreBundle\EventListener
 */
class CourseListener
{
    protected $toolChain;

    /**
     * @param ToolChain $toolChain
     */
    public function __construct(ToolChain $toolChain)
    {
        $this->toolChain = $toolChain;
    }

    /**
     * new object : prePersist
     * edited object: preUpdate
     * @param Course $course
     * @param LifecycleEventArgs $args
     */
    public function prePersist(Course $course, LifecycleEventArgs $args)
    {
        //$this->toolChain->addToolsInCourse($course);
        /*
        error_log('ddd');
        $course->setDescription( ' dq sdqs dqs dqs ');

        $args->getEntityManager()->persist($course);
        $args->getEntityManager()->flush();*/
    }
}
