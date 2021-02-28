<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackCourseRanking.
 *
 * @ORM\Table(name="track_course_ranking", indexes={
 *     @ORM\Index(name="idx_tcc_cid", columns={"c_id"}),
 *     @ORM\Index(name="idx_tcc_sid", columns={"session_id"}),
 *     @ORM\Index(name="idx_tcc_urlid", columns={"url_id"}),
 *     @ORM\Index(name="idx_tcc_creation_date", columns={"creation_date"})
 * })
 * @ORM\Entity
 */
class TrackCourseRanking
{
    /**
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected int $cId;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="url_id", type="integer", nullable=false)
     */
    protected int $urlId;

    /**
     * @ORM\Column(name="accesses", type="integer", nullable=false)
     */
    protected int $accesses;

    /**
     * @ORM\Column(name="total_score", type="integer", nullable=false)
     */
    protected int $totalScore;

    /**
     * @ORM\Column(name="users", type="integer", nullable=false)
     */
    protected int $users;

    /**
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    protected DateTime $creationDate;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return TrackCourseRanking
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return TrackCourseRanking
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set urlId.
     *
     * @param int $urlId
     *
     * @return TrackCourseRanking
     */
    public function setUrlId($urlId)
    {
        $this->urlId = $urlId;

        return $this;
    }

    /**
     * Get urlId.
     *
     * @return int
     */
    public function getUrlId()
    {
        return $this->urlId;
    }

    /**
     * Set accesses.
     *
     * @param int $accesses
     *
     * @return TrackCourseRanking
     */
    public function setAccesses($accesses)
    {
        $this->accesses = $accesses;

        return $this;
    }

    /**
     * Get accesses.
     *
     * @return int
     */
    public function getAccesses()
    {
        return $this->accesses;
    }

    /**
     * Set totalScore.
     *
     * @param int $totalScore
     *
     * @return TrackCourseRanking
     */
    public function setTotalScore($totalScore)
    {
        $this->totalScore = $totalScore;

        return $this;
    }

    /**
     * Get totalScore.
     *
     * @return int
     */
    public function getTotalScore()
    {
        return $this->totalScore;
    }

    /**
     * Set users.
     *
     * @param int $users
     *
     * @return TrackCourseRanking
     */
    public function setUsers($users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * Get users.
     *
     * @return int
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set creationDate.
     *
     * @param DateTime $creationDate
     *
     * @return TrackCourseRanking
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
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
}
