<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpIvInteraction.
 *
 * @ORM\Table(
 *     name="c_lp_iv_interaction",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="lp_iv_id", columns={"lp_iv_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CLpIvInteraction
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
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    protected int $orderId;

    /**
     * @ORM\Column(name="lp_iv_id", type="bigint", nullable=false)
     */
    protected int $lpIvId;

    /**
     * @ORM\Column(name="interaction_id", type="string", length=255, nullable=false)
     */
    protected string $interactionId;

    /**
     * @ORM\Column(name="interaction_type", type="string", length=255, nullable=false)
     */
    protected string $interactionType;

    /**
     * @ORM\Column(name="weighting", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $weighting;

    /**
     * @ORM\Column(name="completion_time", type="string", length=16, nullable=false)
     */
    protected string $completionTime;

    /**
     * @ORM\Column(name="correct_responses", type="text", nullable=false)
     */
    protected string $correctResponses;

    /**
     * @ORM\Column(name="student_response", type="text", nullable=false)
     */
    protected string $studentResponse;

    /**
     * @ORM\Column(name="result", type="string", length=255, nullable=false)
     */
    protected string $result;

    /**
     * @ORM\Column(name="latency", type="string", length=16, nullable=false)
     */
    protected string $latency;

    /**
     * Set orderId.
     *
     * @return CLpIvInteraction
     */
    public function setOrderId(int $orderId)
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
     * @return CLpIvInteraction
     */
    public function setLpIvId(int $lpIvId)
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
     * @return CLpIvInteraction
     */
    public function setInteractionId(string $interactionId)
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
     * @return CLpIvInteraction
     */
    public function setInteractionType(string $interactionType)
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
     * @return CLpIvInteraction
     */
    public function setWeighting(float $weighting)
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
     * @return CLpIvInteraction
     */
    public function setCompletionTime(string $completionTime)
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
     * @return CLpIvInteraction
     */
    public function setCorrectResponses(string $correctResponses)
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
     * @return CLpIvInteraction
     */
    public function setStudentResponse(string $studentResponse)
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
     * @return CLpIvInteraction
     */
    public function setResult(string $result)
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
     * @return CLpIvInteraction
     */
    public function setLatency(string $latency)
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
     * Set cId.
     *
     * @return CLpIvInteraction
     */
    public function setCId(int $cId)
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
