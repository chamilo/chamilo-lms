<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEAttemptCoeff.
 *
 * @ORM\Table(name="track_e_attempt_coeff")
 * @ORM\Entity
 */
class TrackEAttemptCoeff
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="attempt_id", type="integer", nullable=false)
     */
    protected $attemptId;

    /**
     * @var float
     *
     * @ORM\Column(name="marks_coeff", type="float", precision=6, scale=2, nullable=true)
     */
    protected $marksCoeff;

    /**
     * Set attemptId.
     *
     * @param int $attemptId
     *
     * @return TrackEAttemptCoeff
     */
    public function setAttemptId($attemptId)
    {
        $this->attemptId = $attemptId;

        return $this;
    }

    /**
     * Get attemptId.
     *
     * @return int
     */
    public function getAttemptId()
    {
        return $this->attemptId;
    }

    /**
     * Set marksCoeff.
     *
     * @param float $marksCoeff
     *
     * @return TrackEAttemptCoeff
     */
    public function setMarksCoeff($marksCoeff)
    {
        $this->marksCoeff = $marksCoeff;

        return $this;
    }

    /**
     * Get marksCoeff.
     *
     * @return float
     */
    public function getMarksCoeff()
    {
        return $this->marksCoeff;
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
