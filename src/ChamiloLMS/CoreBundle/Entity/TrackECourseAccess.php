<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackECourseAccess
 *
 * @ORM\Table(name="track_e_course_access", indexes={@ORM\Index(name="user_id", columns={"user_id"}), @ORM\Index(name="login_course_date", columns={"login_course_date"}), @ORM\Index(name="c_id", columns={"c_id"}), @ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity
 */
class TrackECourseAccess
{
    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="login_course_date", type="datetime", nullable=false)
     */
    private $loginCourseDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logout_course_date", type="datetime", nullable=true)
     */
    private $logoutCourseDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="counter", type="integer", nullable=false)
     */
    private $counter;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="course_access_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $courseAccessId;


}
