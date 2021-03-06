<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="attempt_id", type="integer", nullable=false)
     */
    protected int $attemptId;

    /**
     * @ORM\Column(name="marks_coeff", type="float", precision=6, scale=2, nullable=true)
     */
    protected ?float $marksCoeff = null;

    /**
     * Set attemptId.
     *
     * @return TrackEAttemptCoeff
     */
    public function setAttemptId(int $attemptId)
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
     * @return TrackEAttemptCoeff
     */
    public function setMarksCoeff(float $marksCoeff)
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
