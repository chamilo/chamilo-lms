<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpIvInteraction
 *
 * @ORM\Table(name="c_lp_iv_interaction", indexes={@ORM\Index(name="lp_iv_id", columns={"lp_iv_id"})})
 * @ORM\Entity
 */
class CLpIvInteraction
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
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    private $orderId;

    /**
     * @var integer
     *
     * @ORM\Column(name="lp_iv_id", type="bigint", nullable=false)
     */
    private $lpIvId;

    /**
     * @var string
     *
     * @ORM\Column(name="interaction_id", type="string", length=255, nullable=false)
     */
    private $interactionId;

    /**
     * @var string
     *
     * @ORM\Column(name="interaction_type", type="string", length=255, nullable=false)
     */
    private $interactionType;

    /**
     * @var float
     *
     * @ORM\Column(name="weighting", type="float", precision=10, scale=0, nullable=false)
     */
    private $weighting;

    /**
     * @var string
     *
     * @ORM\Column(name="completion_time", type="string", length=16, nullable=false)
     */
    private $completionTime;

    /**
     * @var string
     *
     * @ORM\Column(name="correct_responses", type="text", nullable=false)
     */
    private $correctResponses;

    /**
     * @var string
     *
     * @ORM\Column(name="student_response", type="text", nullable=false)
     */
    private $studentResponse;

    /**
     * @var string
     *
     * @ORM\Column(name="result", type="string", length=255, nullable=false)
     */
    private $result;

    /**
     * @var string
     *
     * @ORM\Column(name="latency", type="string", length=16, nullable=false)
     */
    private $latency;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
