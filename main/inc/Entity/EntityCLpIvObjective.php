<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCLpIvObjective
 *
 * @Table(name="c_lp_iv_objective")
 * @Entity
 */
class EntityCLpIvObjective
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="lp_iv_id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lpIvId;

    /**
     * @var integer
     *
     * @Column(name="order_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $orderId;

    /**
     * @var string
     *
     * @Column(name="objective_id", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $objectiveId;

    /**
     * @var float
     *
     * @Column(name="score_raw", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $scoreRaw;

    /**
     * @var float
     *
     * @Column(name="score_max", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $scoreMax;

    /**
     * @var float
     *
     * @Column(name="score_min", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $scoreMin;

    /**
     * @var string
     *
     * @Column(name="status", type="string", length=32, precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCLpIvObjective
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

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCLpIvObjective
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
     * Set lpIvId
     *
     * @param integer $lpIvId
     * @return EntityCLpIvObjective
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
     * @return EntityCLpIvObjective
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
     * @return EntityCLpIvObjective
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
     * @return EntityCLpIvObjective
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
     * @return EntityCLpIvObjective
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
     * @return EntityCLpIvObjective
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
     * @return EntityCLpIvObjective
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
}
