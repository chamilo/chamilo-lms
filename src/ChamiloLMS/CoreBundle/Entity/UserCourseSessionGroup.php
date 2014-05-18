<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CItemProperty
 *
 */
class UserCourseSessionGroup
{
    /**
     * @ORM\ManyToOne(targetEntity="Course", inversedBy="userCourseSessionGroup")
     * @ORM\JoinColumn(name="id", referencedColumnName="id", nullable=false)
     */
    private $course;

    /**
     * @ORM\@ManyToOne(targetEntity="User", inversedBy="userCourseSessionGroup")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Events", inversedBy="userCourseSessionGroup")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", nullable=false)
     */
    //private $session;

}
