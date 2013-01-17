<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCLpIvInteraction
 *
 * @Table(name="c_lp_iv_interaction")
 * @Entity
 */
class EntityCLpIvInteraction
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
     * @Column(name="order_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $orderId;

    /**
     * @var integer
     *
     * @Column(name="lp_iv_id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lpIvId;

    /**
     * @var string
     *
     * @Column(name="interaction_id", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $interactionId;

    /**
     * @var string
     *
     * @Column(name="interaction_type", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $interactionType;

    /**
     * @var float
     *
     * @Column(name="weighting", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $weighting;

    /**
     * @var string
     *
     * @Column(name="completion_time", type="string", length=16, precision=0, scale=0, nullable=false, unique=false)
     */
    private $completionTime;

    /**
     * @var string
     *
     * @Column(name="correct_responses", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $correctResponses;

    /**
     * @var string
     *
     * @Column(name="student_response", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $studentResponse;

    /**
     * @var string
     *
     * @Column(name="result", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $result;

    /**
     * @var string
     *
     * @Column(name="latency", type="string", length=16, precision=0, scale=0, nullable=false, unique=false)
     */
    private $latency;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCLpIvInteraction
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
     * @return EntityCLpIvInteraction
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
     * Set orderId
     *
     * @param integer $orderId
     * @return EntityCLpIvInteraction
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
     * Set lpIvId
     *
     * @param integer $lpIvId
     * @return EntityCLpIvInteraction
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
     * Set interactionId
     *
     * @param string $interactionId
     * @return EntityCLpIvInteraction
     */
    public function setInteractionId($interactionId)
    {
        $this->interactionId = $interactionId;

        return $this;
    }

    /**
     * Get interactionId
     *
     * @return string 
     */
    public function getInteractionId()
    {
        return $this->interactionId;
    }

    /**
     * Set interactionType
     *
     * @param string $interactionType
     * @return EntityCLpIvInteraction
     */
    public function setInteractionType($interactionType)
    {
        $this->interactionType = $interactionType;

        return $this;
    }

    /**
     * Get interactionType
     *
     * @return string 
     */
    public function getInteractionType()
    {
        return $this->interactionType;
    }

    /**
     * Set weighting
     *
     * @param float $weighting
     * @return EntityCLpIvInteraction
     */
    public function setWeighting($weighting)
    {
        $this->weighting = $weighting;

        return $this;
    }

    /**
     * Get weighting
     *
     * @return float 
     */
    public function getWeighting()
    {
        return $this->weighting;
    }

    /**
     * Set completionTime
     *
     * @param string $completionTime
     * @return EntityCLpIvInteraction
     */
    public function setCompletionTime($completionTime)
    {
        $this->completionTime = $completionTime;

        return $this;
    }

    /**
     * Get completionTime
     *
     * @return string 
     */
    public function getCompletionTime()
    {
        return $this->completionTime;
    }

    /**
     * Set correctResponses
     *
     * @param string $correctResponses
     * @return EntityCLpIvInteraction
     */
    public function setCorrectResponses($correctResponses)
    {
        $this->correctResponses = $correctResponses;

        return $this;
    }

    /**
     * Get correctResponses
     *
     * @return string 
     */
    public function getCorrectResponses()
    {
        return $this->correctResponses;
    }

    /**
     * Set studentResponse
     *
     * @param string $studentResponse
     * @return EntityCLpIvInteraction
     */
    public function setStudentResponse($studentResponse)
    {
        $this->studentResponse = $studentResponse;

        return $this;
    }

    /**
     * Get studentResponse
     *
     * @return string 
     */
    public function getStudentResponse()
    {
        return $this->studentResponse;
    }

    /**
     * Set result
     *
     * @param string $result
     * @return EntityCLpIvInteraction
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result
     *
     * @return string 
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set latency
     *
     * @param string $latency
     * @return EntityCLpIvInteraction
     */
    public function setLatency($latency)
    {
        $this->latency = $latency;

        return $this;
    }

    /**
     * Get latency
     *
     * @return string 
     */
    public function getLatency()
    {
        return $this->latency;
    }
}
