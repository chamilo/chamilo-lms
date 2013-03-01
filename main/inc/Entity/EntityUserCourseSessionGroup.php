<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * EntityCItemProperty
 *

 */
class EntityUserCourseSessionGroup
{
    /**
     * @ManyToOne(targetEntity="EntityCourse", inversedBy="entityUserCourseSessionGroup")
     * @JoinColumn(name="id", referencedColumnName="id", nullable=false)
     */
    private $course;

    /**
     * @ManyToOne(targetEntity="EntityUser", inversedBy="entityUserCourseSessionGroup")
     * @JoinColumn(name="user_id", referencedColumnName="user_id", nullable=false)
     */
    private $user;

    /**
     * @ManyToOne(targetEntity="Events", inversedBy="entityUserCourseSessionGroup")
     * @JoinColumn(name="event_id", referencedColumnName="id", nullable=false)
     */
    //private $session;

}