<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpIvObjective.
 *
 * @ORM\Table(
 *     name="c_lp_iv_objective",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="lp_iv_id", columns={"lp_iv_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CLpIvObjective
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="lp_iv_id", type="bigint", nullable=false)
     */
    protected int $lpIvId;

    /**
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    protected int $orderId;

    /**
     * @ORM\Column(name="objective_id", type="string", length=255, nullable=false)
     */
    protected string $objectiveId;

    /**
     * @ORM\Column(name="score_raw", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $scoreRaw;

    /**
     * @ORM\Column(name="score_max", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $scoreMax;

    /**
     * @ORM\Column(name="score_min", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $scoreMin;

    /**
     * @ORM\Column(name="status", type="string", length=32, nullable=false)
     */
    protected string $status;

    /**
     * Set lpIvId.
     *
     * @param int $lpIvId
     *
     * @return CLpIvObjective
     */
    public function setLpIvId($lpIvId)
    {
        $this->lpIvId = $lpIvId;

        return $this;
    }

    /**
     * Get lpIvId.
     *
     * @return int
     */
    public function getLpIvId()
    {
        return $this->lpIvId;
    }

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return CLpIvObjective
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set objectiveId.
     *
     * @param string $objectiveId
     *
     * @return CLpIvObjective
     */
    public function setObjectiveId($objectiveId)
    {
        $this->objectiveId = $objectiveId;

        return $this;
    }

    /**
     * Get objectiveId.
     *
     * @return string
     */
    public function getObjectiveId()
    {
        return $this->objectiveId;
    }

    /**
     * Set scoreRaw.
     *
     * @param float $scoreRaw
     *
     * @return CLpIvObjective
     */
    public function setScoreRaw($scoreRaw)
    {
        $this->scoreRaw = $scoreRaw;

        return $this;
    }

    /**
     * Get scoreRaw.
     *
     * @return float
     */
    public function getScoreRaw()
    {
        return $this->scoreRaw;
    }

    /**
     * Set scoreMax.
     *
     * @param float $scoreMax
     *
     * @return CLpIvObjective
     */
    public function setScoreMax($scoreMax)
    {
        $this->scoreMax = $scoreMax;

        return $this;
    }

    /**
     * Get scoreMax.
     *
     * @return float
     */
    public function getScoreMax()
    {
        return $this->scoreMax;
    }

    /**
     * Set scoreMin.
     *
     * @param float $scoreMin
     *
     * @return CLpIvObjective
     */
    public function setScoreMin($scoreMin)
    {
        $this->scoreMin = $scoreMin;

        return $this;
    }

    /**
     * Get scoreMin.
     *
     * @return float
     */
    public function getScoreMin()
    {
        return $this->scoreMin;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return CLpIvObjective
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CLpIvObjective
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }
}
