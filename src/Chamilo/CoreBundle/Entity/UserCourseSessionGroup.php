<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserCourseSessionGroup
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
     * @ORM\@ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="userCourseSessionGroup")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Events", inversedBy="userCourseSessionGroup")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", nullable=false)
     */
    //private $session;

}
