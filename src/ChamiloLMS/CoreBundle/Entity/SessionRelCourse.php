<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SessionRelCourse
 *
 * @ORM\Table(name="session_rel_course", indexes={@ORM\Index(name="idx_session_rel_course_course_id", columns={"c_id"})})
 * @ORM\Entity
 */
class SessionRelCourse
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_session", type="integer")
     */
    //private $idSession;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    //private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="nbr_users", type="integer")
     */
    private $nbrUsers;

    /**
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="courses", cascade={"persist"})
     * @ORM\JoinColumn(name="id_session", referencedColumnName="id")
     */
    private $session;

    /**
     * @ORM\ManyToOne(targetEntity="Course", inversedBy="sessions", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;


    public function __construct()
    {
        $this->nbrUsers = 0;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $session
     * @return $this
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get Session
     *
     * @return string
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param $course
     * @return $this
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get Session
     *
     * @return string
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set idSession
     *
     * @param integer $idSession
     * @return SessionRelCourse
     */
    public function setIdSession($idSession)
    {
        $this->idSession = $idSession;

        return $this;
    }

    /**
     * Get idSession
     *
     * @return integer
     */
    public function getIdSession()
    {
        return $this->idSession;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return SessionRelCourse
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set nbrUsers
     *
     * @param integer $nbrUsers
     * @return SessionRelCourse
     */
    public function setNbrUsers($nbrUsers)
    {
        $this->nbrUsers = $nbrUsers;

        return $this;
    }

    /**
     * Get nbrUsers
     *
     * @return integer
     */
    public function getNbrUsers()
    {
        return $this->nbrUsers;
    }
}
