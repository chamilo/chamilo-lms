<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackCourseRanking
 *
 * @Table(name="track_course_ranking")
 * @Entity
 */
class EntityTrackCourseRanking
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @Column(name="url_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $urlId;

    /**
     * @var integer
     *
     * @Column(name="accesses", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accesses;

    /**
     * @var integer
     *
     * @Column(name="total_score", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $totalScore;

    /**
     * @var integer
     *
     * @Column(name="users", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $users;

    /**
     * @var \DateTime
     *
     * @Column(name="creation_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $creationDate;


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
     * Set cId
     *
     * @param integer $cId
     * @return EntityTrackCourseRanking
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
     * @return EntityTrackCourseRanking
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
     * @return EntityTrackCourseRanking
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
     * @return EntityTrackCourseRanking
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
     * @return EntityTrackCourseRanking
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
     * @return EntityTrackCourseRanking
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
     * @return EntityTrackCourseRanking
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
}
