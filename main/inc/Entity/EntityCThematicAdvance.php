<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCThematicAdvance
 *
 * @Table(name="c_thematic_advance")
 * @Entity
 */
class EntityCThematicAdvance
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
     * @Column(name="thematic_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $thematicId;

    /**
     * @var integer
     *
     * @Column(name="attendance_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attendanceId;

    /**
     * @var string
     *
     * @Column(name="content", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $content;

    /**
     * @var \DateTime
     *
     * @Column(name="start_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $startDate;

    /**
     * @var integer
     *
     * @Column(name="duration", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $duration;

    /**
     * @var boolean
     *
     * @Column(name="done_advance", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $doneAdvance;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCThematicAdvance
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
     * @return EntityCThematicAdvance
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
     * Set thematicId
     *
     * @param integer $thematicId
     * @return EntityCThematicAdvance
     */
    public function setThematicId($thematicId)
    {
        $this->thematicId = $thematicId;

        return $this;
    }

    /**
     * Get thematicId
     *
     * @return integer 
     */
    public function getThematicId()
    {
        return $this->thematicId;
    }

    /**
     * Set attendanceId
     *
     * @param integer $attendanceId
     * @return EntityCThematicAdvance
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
     * Set content
     *
     * @param string $content
     * @return EntityCThematicAdvance
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return EntityCThematicAdvance
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     * @return EntityCThematicAdvance
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer 
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set doneAdvance
     *
     * @param boolean $doneAdvance
     * @return EntityCThematicAdvance
     */
    public function setDoneAdvance($doneAdvance)
    {
        $this->doneAdvance = $doneAdvance;

        return $this;
    }

    /**
     * Get doneAdvance
     *
     * @return boolean 
     */
    public function getDoneAdvance()
    {
        return $this->doneAdvance;
    }
}
