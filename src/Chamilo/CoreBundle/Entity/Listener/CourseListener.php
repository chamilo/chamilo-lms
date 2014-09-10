<?php

namespace Chamilo\CoreBundle\Entity\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Chamilo\CoreBundle\Entity\Course;

/**
 * Class CourseListener
 * Course entity listener, when a course is created the tool chain is loaded.
 * @package Chamilo\CoreBundle\EventListener
 */
class CourseListener
{
    private $toolChain;

    /**
     * @param $toolChain
     */
    public function __construct($toolChain)
    {
        $this->toolChain = $toolChain;
    }

    /**
     * new object : prePersist
     * edited object: preUpdate
     * @param LifecycleEventArgs $args
     */
    public function prePersist(Course $course, LifecycleEventArgs $args)
    {
        foreach ($this->toolChain as $tool) {
            $tool->getName();
        }
        $course->setDescription( ' dq sdqs dqs dqs ');
        $args->getEntityManager()->persist($course);
        $args->getEntityManager()->flush();

    }
}
