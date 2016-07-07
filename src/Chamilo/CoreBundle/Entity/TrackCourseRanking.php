<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackCourseRanking
 *
 * @ORM\Table(name="track_course_ranking", indexes={@ORM\Index(name="idx_tcc_cid", columns={"c_id"}), @ORM\Index(name="idx_tcc_sid", columns={"session_id"}), @ORM\Index(name="idx_tcc_urlid", columns={"url_id"}), @ORM\Index(name="idx_tcc_creation_date", columns={"creation_date"})})
 * @ORM\Entity
 */
class TrackCourseRanking
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="url_id", type="integer", nullable=false)
     */
    private $urlId;

    /**
     * @var integer
     *
     * @ORM\Column(name="accesses", type="integer", nullable=false)
     */
    private $accesses;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_score", type="integer", nullable=false)
     */
    private $totalScore;

    /**
     * @var integer
     *
     * @ORM\Column(name="users", type="integer", nullable=false)
     */
    private $users;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set cId
     *
     * @param integer $cId
     * @return TrackCourseRanking
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
     * Set sessionId
     *
     * @param integer $sessionId
     * @return TrackCourseRanking
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set urlId
     *
     * @param integer $urlId
     * @return TrackCourseRanking
     */
    public function setUrlId($urlId)
    {
        $this->urlId = $urlId;

        return $this;
    }

    /**
     * Get urlId
     *
     * @return integer
     */
    public function getUrlId()
    {
        return $this->urlId;
    }

    /**
     * Set accesses
     *
     * @param integer $accesses
     * @return TrackCourseRanking
     */
    public function setAccesses($accesses)
    {
        $this->accesses = $accesses;

        return $this;
    }

    /**
     * Get accesses
     *
     * @return integer
     */
    public function getAccesses()
    {
        return $this->accesses;
    }

    /**
     * Set totalScore
     *
     * @param integer $totalScore
     * @return TrackCourseRanking
     */
    public function setTotalScore($totalScore)
    {
        $this->totalScore = $totalScore;

        return $this;
    }

    /**
     * Get totalScore
     *
     * @return integer
     */
    public function getTotalScore()
    {
        return $this->totalScore;
    }

    /**
     * Set users
     *
     * @param integer $users
     * @return TrackCourseRanking
     */
    public function setUsers($users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * Get users
     *
     * @return integer
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     * @return TrackCourseRanking
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
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
}
