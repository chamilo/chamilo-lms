<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEHotpotatoes
 *
 * @ORM\Table(name="track_e_hotpotatoes")
 * @ORM\Entity
 */
class TrackEHotpotatoes
{
    /**
     * @var string
     *
     * @ORM\Column(name="exe_name", type="string", length=255, nullable=false)
     */
    private $exeName;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_user_id", type="integer", nullable=true)
     */
    private $exeUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="exe_date", type="datetime", nullable=false)
     */
    private $exeDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_result", type="smallint", nullable=false)
     */
    private $exeResult;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_weighting", type="smallint", nullable=false)
     */
    private $exeWeighting;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
