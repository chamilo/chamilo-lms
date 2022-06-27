<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SessionRelCourseRelUser.
 *
 * @ORM\Table(
 *      name="session_rel_course_rel_user",
 *      indexes={
 *          @ORM\Index(name="idx_session_rel_course_rel_user_id_user", columns={"user_id"}),
 *          @ORM\Index(name="idx_session_rel_course_rel_user_course_id", columns={"c_id"})
 *      }
 * )
 * @ORM\Entity
 */
class SessionRelCourseRelUser
{
    public const STATUS_STUDENT = 0;
    public const STATUS_COURSE_COACH = 2;

    public $statusList = [
        0 => 'student',
        2 => 'course_coach',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="sessionCourseSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var Session
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="userCourseSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=false)
     */
    protected $session;

    /**
     * @var Course
     * @ORM\ManyToOne(targetEntity="Course", inversedBy="sessionUserSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    protected $course;

    /**
     * @var int
     *
     * @ORM\Column(name="visibility", type="integer", nullable=false, unique=false)
     */
    protected $visibility;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false, unique=false)
     */
    protected $status;

    /**
     * @var int
     *
     * @ORM\Column(name="legal_agreement", type="integer", nullable=true, unique=false)
     */
    protected $legalAgreement;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->visibility = 1;
    }

    /**
     * @return \Chamilo\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param Course $course
     */
    public function setCourse($course)
    {
        $this->course = $course;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set visibility.
     *
     * @param int $visibility
     *
     * @return SessionRelCourseRelUser
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility.
     *
     * @return int
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return SessionRelCourseRelUser
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set legalAgreement.
     *
     * @param int $legalAgreement
     *
     * @return SessionRelCourseRelUser
     */
    public function setLegalAgreement($legalAgreement)
    {
        $this->legalAgreement = $legalAgreement;

        return $this;
    }

    /**
     * Get legalAgreement.
     *
     * @return int
     */
    public function getLegalAgreement()
    {
        return $this->legalAgreement;
    }
}
