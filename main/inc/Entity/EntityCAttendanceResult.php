<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCAttendanceResult
 *
 * @Table(name="c_attendance_result")
 * @Entity
 */
class EntityCAttendanceResult
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="attendance_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attendanceId;

    /**
     * @var integer
     *
     * @Column(name="score", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $score;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCAttendanceResult
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
     * Set id
     *
     * @param integer $id
     * @return EntityCAttendanceResult
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set userId
     *
     * @param integer $userId
     * @return EntityCAttendanceResult
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set attendanceId
     *
     * @param integer $attendanceId
     * @return EntityCAttendanceResult
     */
    public function setAttendanceId($attendanceId)
    {
        $this->attendanceId = $attendanceId;

        return $this;
    }

    /**
     * Get attendanceId
     *
     * @return integer 
     */
    public function getAttendanceId()
    {
        return $this->attendanceId;
    }

    /**
     * Set score
     *
     * @param integer $score
     * @return EntityCAttendanceResult
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return integer 
     */
    public function getScore()
    {
        return $this->score;
    }
}
