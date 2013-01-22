<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackEHotspot
 *
 * @Table(name="track_e_hotspot")
 * @Entity
 */
class EntityTrackEHotspot
{
    /**
     * @var integer
     *
     * @Column(name="hotspot_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $hotspotId;

    /**
     * @var integer
     *
     * @Column(name="hotspot_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotUserId;

    /**
     * @var string
     *
     * @Column(name="hotspot_course_code", type="string", length=50, precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotCourseCode;

    /**
     * @var integer
     *
     * @Column(name="hotspot_exe_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotExeId;

    /**
     * @var integer
     *
     * @Column(name="hotspot_question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotQuestionId;

    /**
     * @var integer
     *
     * @Column(name="hotspot_answer_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotAnswerId;

    /**
     * @var boolean
     *
     * @Column(name="hotspot_correct", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotCorrect;

    /**
     * @var string
     *
     * @Column(name="hotspot_coordinate", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotCoordinate;


    /**
     * Get hotspotId
     *
     * @return integer 
     */
    public function getHotspotId()
    {
        return $this->hotspotId;
    }

    /**
     * Set hotspotUserId
     *
     * @param integer $hotspotUserId
     * @return EntityTrackEHotspot
     */
    public function setHotspotUserId($hotspotUserId)
    {
        $this->hotspotUserId = $hotspotUserId;

        return $this;
    }

    /**
     * Get hotspotUserId
     *
     * @return integer 
     */
    public function getHotspotUserId()
    {
        return $this->hotspotUserId;
    }

    /**
     * Set hotspotCourseCode
     *
     * @param string $hotspotCourseCode
     * @return EntityTrackEHotspot
     */
    public function setHotspotCourseCode($hotspotCourseCode)
    {
        $this->hotspotCourseCode = $hotspotCourseCode;

        return $this;
    }

    /**
     * Get hotspotCourseCode
     *
     * @return string 
     */
    public function getHotspotCourseCode()
    {
        return $this->hotspotCourseCode;
    }

    /**
     * Set hotspotExeId
     *
     * @param integer $hotspotExeId
     * @return EntityTrackEHotspot
     */
    public function setHotspotExeId($hotspotExeId)
    {
        $this->hotspotExeId = $hotspotExeId;

        return $this;
    }

    /**
     * Get hotspotExeId
     *
     * @return integer 
     */
    public function getHotspotExeId()
    {
        return $this->hotspotExeId;
    }

    /**
     * Set hotspotQuestionId
     *
     * @param integer $hotspotQuestionId
     * @return EntityTrackEHotspot
     */
    public function setHotspotQuestionId($hotspotQuestionId)
    {
        $this->hotspotQuestionId = $hotspotQuestionId;

        return $this;
    }

    /**
     * Get hotspotQuestionId
     *
     * @return integer 
     */
    public function getHotspotQuestionId()
    {
        return $this->hotspotQuestionId;
    }

    /**
     * Set hotspotAnswerId
     *
     * @param integer $hotspotAnswerId
     * @return EntityTrackEHotspot
     */
    public function setHotspotAnswerId($hotspotAnswerId)
    {
        $this->hotspotAnswerId = $hotspotAnswerId;

        return $this;
    }

    /**
     * Get hotspotAnswerId
     *
     * @return integer 
     */
    public function getHotspotAnswerId()
    {
        return $this->hotspotAnswerId;
    }

    /**
     * Set hotspotCorrect
     *
     * @param boolean $hotspotCorrect
     * @return EntityTrackEHotspot
     */
    public function setHotspotCorrect($hotspotCorrect)
    {
        $this->hotspotCorrect = $hotspotCorrect;

        return $this;
    }

    /**
     * Get hotspotCorrect
     *
     * @return boolean 
     */
    public function getHotspotCorrect()
    {
        return $this->hotspotCorrect;
    }

    /**
     * Set hotspotCoordinate
     *
     * @param string $hotspotCoordinate
     * @return EntityTrackEHotspot
     */
    public function setHotspotCoordinate($hotspotCoordinate)
    {
        $this->hotspotCoordinate = $hotspotCoordinate;

        return $this;
    }

    /**
     * Get hotspotCoordinate
     *
     * @return string 
     */
    public function getHotspotCoordinate()
    {
        return $this->hotspotCoordinate;
    }
}
