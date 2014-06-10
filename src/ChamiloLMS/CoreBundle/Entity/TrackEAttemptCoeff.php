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
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
