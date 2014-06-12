<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEAttemptCoeff
 *
 * @ORM\Table(name="track_e_attempt_coeff")
 * @ORM\Entity
 */
class TrackEAttemptCoeff
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="attempt_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attemptId;

    /**
     * @var float
     *
     * @ORM\Column(name="marks_coeff", type="float", precision=10, scale=0, nullable=true, unique=false)
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
     * @return TrackEAttemptCoeff
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
     * @return TrackEAttemptCoeff
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
