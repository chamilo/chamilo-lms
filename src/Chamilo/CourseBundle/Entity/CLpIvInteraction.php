<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpIvInteraction.
 *
 * @ORM\Table(
 *  name="c_lp_iv_interaction",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="lp_iv_id", columns={"lp_iv_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CLpIvInteraction
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    protected $orderId;

    /**
     * @var int
     *
     * @ORM\Column(name="lp_iv_id", type="bigint", nullable=false)
     */
    protected $lpIvId;

    /**
     * @var string
     *
     * @ORM\Column(name="interaction_id", type="string", length=255, nullable=false)
     */
    protected $interactionId;

    /**
     * @var string
     *
     * @ORM\Column(name="interaction_type", type="string", length=255, nullable=false)
     */
    protected $interactionType;

    /**
     * @var float
     *
     * @ORM\Column(name="weighting", type="float", precision=10, scale=0, nullable=false)
     */
    protected $weighting;

    /**
     * @var string
     *
     * @ORM\Column(name="completion_time", type="string", length=16, nullable=false)
     */
    protected $completionTime;

    /**
     * @var string
     *
     * @ORM\Column(name="correct_responses", type="text", nullable=false)
     */
    protected $correctResponses;

    /**
     * @var string
     *
     * @ORM\Column(name="student_response", type="text", nullable=false)
     */
    protected $studentResponse;

    /**
     * @var string
     *
     * @ORM\Column(name="result", type="string", length=255, nullable=false)
     */
    protected $result;

    /**
     * @var string
     *
     * @ORM\Column(name="latency", type="string", length=16, nullable=false)
     */
    protected $latency;

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return CLpIvInteraction
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
     * Set lpIvId.
     *
     * @param int $lpIvId
     *
     * @return CLpIvInteraction
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
     * Set interactionId.
     *
     * @param string $interactionId
     *
     * @return CLpIvInteraction
     */
    public function setInteractionId($interactionId)
    {
        $this->interactionId = $interactionId;

        return $this;
    }

    /**
     * Get interactionId.
     *
     * @return string
     */
    public function getInteractionId()
    {
        return $this->interactionId;
    }

    /**
     * Set interactionType.
     *
     * @param string $interactionType
     *
     * @return CLpIvInteraction
     */
    public function setInteractionType($interactionType)
    {
        $this->interactionType = $interactionType;

        return $this;
    }

    /**
     * Get interactionType.
     *
     * @return string
     */
    public function getInteractionType()
    {
        return $this->interactionType;
    }

    /**
     * Set weighting.
     *
     * @param float $weighting
     *
     * @return CLpIvInteraction
     */
    public function setWeighting($weighting)
    {
        $this->weighting = $weighting;

        return $this;
    }

    /**
     * Get weighting.
     *
     * @return float
     */
    public function getWeighting()
    {
        return $this->weighting;
    }

    /**
     * Set completionTime.
     *
     * @param string $completionTime
     *
     * @return CLpIvInteraction
     */
    public function setCompletionTime($completionTime)
    {
        $this->completionTime = $completionTime;

        return $this;
    }

    /**
     * Get completionTime.
     *
     * @return string
     */
    public function getCompletionTime()
    {
        return $this->completionTime;
    }

    /**
     * Set correctResponses.
     *
     * @param string $correctResponses
     *
     * @return CLpIvInteraction
     */
    public function setCorrectResponses($correctResponses)
    {
        $this->correctResponses = $correctResponses;

        return $this;
    }

    /**
     * Get correctResponses.
     *
     * @return string
     */
    public function getCorrectResponses()
    {
        return $this->correctResponses;
    }

    /**
     * Set studentResponse.
     *
     * @param string $studentResponse
     *
     * @return CLpIvInteraction
     */
    public function setStudentResponse($studentResponse)
    {
        $this->studentResponse = $studentResponse;

        return $this;
    }

    /**
     * Get studentResponse.
     *
     * @return string
     */
    public function getStudentResponse()
    {
        return $this->studentResponse;
    }

    /**
     * Set result.
     *
     * @param string $result
     *
     * @return CLpIvInteraction
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result.
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set latency.
     *
     * @param string $latency
     *
     * @return CLpIvInteraction
     */
    public function setLatency($latency)
    {
        $this->latency = $latency;

        return $this;
    }

    /**
     * Get latency.
     *
     * @return string
     */
    public function getLatency()
    {
        return $this->latency;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CLpIvInteraction
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CLpIvInteraction
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
