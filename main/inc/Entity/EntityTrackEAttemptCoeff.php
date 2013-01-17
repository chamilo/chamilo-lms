<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackEAttemptCoeff
 *
 * @Table(name="track_e_attempt_coeff")
 * @Entity
 */
class EntityTrackEAttemptCoeff
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
     * @Column(name="attempt_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attemptId;

    /**
     * @var float
     *
     * @Column(name="marks_coeff", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $marksCoeff;


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
     * Set attemptId
     *
     * @param integer $attemptId
     * @return EntityTrackEAttemptCoeff
     */
    public function setAttemptId($attemptId)
    {
        $this->attemptId = $attemptId;

        return $this;
    }

    /**
     * Get attemptId
     *
     * @return integer 
     */
    public function getAttemptId()
    {
        return $this->attemptId;
    }

    /**
     * Set marksCoeff
     *
     * @param float $marksCoeff
     * @return EntityTrackEAttemptCoeff
     */
    public function setMarksCoeff($marksCoeff)
    {
        $this->marksCoeff = $marksCoeff;

        return $this;
    }

    /**
     * Get marksCoeff
     *
     * @return float 
     */
    public function getMarksCoeff()
    {
        return $this->marksCoeff;
    }
}
