<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="attempt_id", type="integer", nullable=false)
     */
    private $attemptId;

    /**
     * @var float
     *
     * @ORM\Column(name="marks_coeff", type="float", precision=6, scale=2, nullable=true)
     */
    private $marksCoeff;

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
