<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizAnswer
 *
 * @ORM\Table(name="c_quiz_answer", indexes={@ORM\Index(name="idx_cqa_qid", columns={"question_id"})})
 * @ORM\Entity
 */
class CQuizAnswer
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
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    private $questionId;

    /**
     * @var string
     *
     * @ORM\Column(name="answer", type="text", nullable=false)
     */
    private $answer;

    /**
     * @var integer
     *
     * @ORM\Column(name="correct", type="integer", nullable=true)
     */
    private $correct;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var float
     *
     * @ORM\Column(name="ponderation", type="float", precision=6, scale=2, nullable=false)
     */
    private $ponderation;

    /**
     * @var integer
     *
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(name="hotspot_coordinates", type="text", nullable=true)
     */
    private $hotspotCoordinates;

    /**
     * @var string
     *
     * @ORM\Column(name="hotspot_type", type="string", length=100, nullable=true)
     */
    private $hotspotType;

    /**
     * @var string
     *
     * @ORM\Column(name="destination", type="text", nullable=false)
     */
    private $destination;

    /**
     * @var string
     *
     * @ORM\Column(name="answer_code", type="string", length=10, nullable=true)
     */
    private $answerCode;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
