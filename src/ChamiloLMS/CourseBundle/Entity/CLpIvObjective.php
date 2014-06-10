<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpIvObjective
 *
 * @ORM\Table(name="c_lp_iv_objective", indexes={@ORM\Index(name="lp_iv_id", columns={"lp_iv_id"})})
 * @ORM\Entity
 */
class CLpIvObjective
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="lp_iv_id", type="bigint", nullable=false)
     */
    private $lpIvId;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    private $orderId;

    /**
     * @var string
     *
     * @ORM\Column(name="objective_id", type="string", length=255, nullable=false)
     */
    private $objectiveId;

    /**
     * @var float
     *
     * @ORM\Column(name="score_raw", type="float", precision=10, scale=0, nullable=false)
     */
    private $scoreRaw;

    /**
     * @var float
     *
     * @ORM\Column(name="score_max", type="float", precision=10, scale=0, nullable=false)
     */
    private $scoreMax;

    /**
     * @var float
     *
     * @ORM\Column(name="score_min", type="float", precision=10, scale=0, nullable=false)
     */
    private $scoreMin;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=32, nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
