<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SessionRelCourse.
 *
 * @ORM\Table(name="session_rel_course", indexes={
 *     @ORM\Index(name="idx_session_rel_course_course_id", columns={"c_id"})
 * })
 * @ORM\Entity
 */
class SessionRelCourse
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="nbr_users", type="integer")
     */
    protected int $nbrUsers;

    /**
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="courses", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=false)
     */
    protected ?\Chamilo\CoreBundle\Entity\Session $session = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="sessions", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    protected ?\Chamilo\CoreBundle\Entity\Course $course = null;

    /**
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    protected int $position;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->nbrUsers = 0;
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
     * @return $this
     */
    public function setSession(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get course.
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @return $this
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get Session.
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set nbrUsers.
     *
     * @return SessionRelCourse
     */
    public function setNbrUsers(int $nbrUsers)
    {
        $this->nbrUsers = $nbrUsers;

        return $this;
    }

    /**
     * Get nbrUsers.
     *
     * @return int
     */
    public function getNbrUsers()
    {
        return $this->nbrUsers;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}
