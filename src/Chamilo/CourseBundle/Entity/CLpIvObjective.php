<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpIvObjective
 *
 * @ORM\Table(
 *  name="c_lp_iv_objective",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="lp_iv_id", columns={"lp_iv_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CLpIvObjective
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
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
     * Set lpIvId
     *
     * @param integer $lpIvId
     * @return CLpIvObjective
     */
    public function setLpIvId($lpIvId)
    {
        $this->lpIvId = $lpIvId;

        return $this;
    }

    /**
     * Get lpIvId
     *
     * @return integer
     */
    public function getLpIvId()
    {
        return $this->lpIvId;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     * @return CLpIvObjective
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set objectiveId
     *
     * @param string $objectiveId
     * @return CLpIvObjective
     */
    public function setObjectiveId($objectiveId)
    {
        $this->objectiveId = $objectiveId;

        return $this;
    }

    /**
     * Get objectiveId
     *
     * @return string
     */
    public function getObjectiveId()
    {
        return $this->objectiveId;
    }

    /**
     * Set scoreRaw
     *
     * @param float $scoreRaw
     * @return CLpIvObjective
     */
    public function setScoreRaw($scoreRaw)
    {
        $this->scoreRaw = $scoreRaw;

        return $this;
    }

    /**
     * Get scoreRaw
     *
     * @return float
     */
    public function getScoreRaw()
    {
        return $this->scoreRaw;
    }

    /**
     * Set scoreMax
     *
     * @param float $scoreMax
     * @return CLpIvObjective
     */
    public function setScoreMax($scoreMax)
    {
        $this->scoreMax = $scoreMax;

        return $this;
    }

    /**
     * Get scoreMax
     *
     * @return float
     */
    public function getScoreMax()
    {
        return $this->scoreMax;
    }

    /**
     * Set scoreMin
     *
     * @param float $scoreMin
     * @return CLpIvObjective
     */
    public function setScoreMin($scoreMin)
    {
        $this->scoreMin = $scoreMin;

        return $this;
    }

    /**
     * Get scoreMin
     *
     * @return float
     */
    public function getScoreMin()
    {
        return $this->scoreMin;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return CLpIvObjective
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CLpIvObjective
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CLpIvObjective
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }
}
