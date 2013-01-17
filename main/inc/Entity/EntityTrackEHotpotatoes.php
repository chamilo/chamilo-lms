<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackEHotpotatoes
 *
 * @Table(name="track_e_hotpotatoes")
 * @Entity
 */
class EntityTrackEHotpotatoes
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
     * @var string
     *
     * @Column(name="exe_name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeName;

    /**
     * @var integer
     *
     * @Column(name="exe_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $exeUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="exe_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeDate;

    /**
     * @var string
     *
     * @Column(name="exe_cours_id", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeCoursId;

    /**
     * @var integer
     *
     * @Column(name="exe_result", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeResult;

    /**
     * @var integer
     *
     * @Column(name="exe_weighting", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeWeighting;


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
     * Set exeName
     *
     * @param string $exeName
     * @return EntityTrackEHotpotatoes
     */
    public function setExeName($exeName)
    {
        $this->exeName = $exeName;

        return $this;
    }

    /**
     * Get exeName
     *
     * @return string 
     */
    public function getExeName()
    {
        return $this->exeName;
    }

    /**
     * Set exeUserId
     *
     * @param integer $exeUserId
     * @return EntityTrackEHotpotatoes
     */
    public function setExeUserId($exeUserId)
    {
        $this->exeUserId = $exeUserId;

        return $this;
    }

    /**
     * Get exeUserId
     *
     * @return integer 
     */
    public function getExeUserId()
    {
        return $this->exeUserId;
    }

    /**
     * Set exeDate
     *
     * @param \DateTime $exeDate
     * @return EntityTrackEHotpotatoes
     */
    public function setExeDate($exeDate)
    {
        $this->exeDate = $exeDate;

        return $this;
    }

    /**
     * Get exeDate
     *
     * @return \DateTime 
     */
    public function getExeDate()
    {
        return $this->exeDate;
    }

    /**
     * Set exeCoursId
     *
     * @param string $exeCoursId
     * @return EntityTrackEHotpotatoes
     */
    public function setExeCoursId($exeCoursId)
    {
        $this->exeCoursId = $exeCoursId;

        return $this;
    }

    /**
     * Get exeCoursId
     *
     * @return string 
     */
    public function getExeCoursId()
    {
        return $this->exeCoursId;
    }

    /**
     * Set exeResult
     *
     * @param integer $exeResult
     * @return EntityTrackEHotpotatoes
     */
    public function setExeResult($exeResult)
    {
        $this->exeResult = $exeResult;

        return $this;
    }

    /**
     * Get exeResult
     *
     * @return integer 
     */
    public function getExeResult()
    {
        return $this->exeResult;
    }

    /**
     * Set exeWeighting
     *
     * @param integer $exeWeighting
     * @return EntityTrackEHotpotatoes
     */
    public function setExeWeighting($exeWeighting)
    {
        $this->exeWeighting = $exeWeighting;

        return $this;
    }

    /**
     * Get exeWeighting
     *
     * @return integer 
     */
    public function getExeWeighting()
    {
        return $this->exeWeighting;
    }
}
