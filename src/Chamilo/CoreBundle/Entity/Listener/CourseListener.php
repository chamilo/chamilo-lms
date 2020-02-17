<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CourseBundle\ToolChain;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Class CourseListener
 * Course entity listener, when a course is created the tool chain is loaded.
 *
 * @package Chamilo\CoreBundle\EventListener
 */
class CourseListener
{
    protected $toolChain;

    public function __construct(ToolChain $toolChain)
    {
        $this->toolChain = $toolChain;
    }

    /**
     * new object : prePersist
     * edited object: preUpdate.
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
