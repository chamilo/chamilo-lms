<?php

namespace ChamiloLMS\CoreBundle\Entity\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use ChamiloLMS\CoreBundle\Entity\Course;

/**
 * Class CourseListener
 * @package ChamiloLMS\CoreBundle\EventListener
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
        var_dump($this->toolChain);

    }
}
