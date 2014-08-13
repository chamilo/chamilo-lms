<?php

namespace Chamilo\CourseBundle\Entity;

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
     * @ORM\Column(name="iid", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;

    /**
     * @var string
     *
     * @ORM\Column(name="answer", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $answer;

    /**
     * @var integer
     *
     * @ORM\Column(name="correct", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $correct;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $comment;

    /**
     * @var float
     *
     * @ORM\Column(name="ponderation", type="float", precision=10, scale=0, nullable=false, unique=false)
     */
    private $ponderation;

    /**
     * @var integer
     *
     * @ORM\Column(name="position", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(name="hotspot_coordinates", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $hotspotCoordinates;

    /**
     * @var string
     *
     * @ORM\Column(name="hotspot_type", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $hotspotType;

    /**
     * @var string
     *
     * @ORM\Column(name="destination", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $destination;

    /**
     * @var string
     *
     * @ORM\Column(name="answer_code", type="string", length=10, precision=0, scale=0, nullable=true, unique=false)
     */
    private $answerCode;


    /**
     * Get iid
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CQuizAnswer
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
     * Set questionId
     *
     * @param integer $questionId
     * @return CQuizAnswer
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;

        return $this;
    }

    /**
     * Get questionId
     *
     * @return integer
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * Set answer
     *
     * @param string $answer
     * @return CQuizAnswer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Get answer
     *
     * @return string
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Set correct
     *
     * @param integer $correct
     * @return CQuizAnswer
     */
    public function setCorrect($correct)
    {
        $this->correct = $correct;

        return $this;
    }

    /**
     * Get correct
     *
     * @return integer
     */
    public function getCorrect()
    {
        return $this->correct;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return CQuizAnswer
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set ponderation
     *
     * @param float $ponderation
     * @return CQuizAnswer
     */
    public function setPonderation($ponderation)
    {
        $this->ponderation = $ponderation;

        return $this;
    }

    /**
     * Get ponderation
     *
     * @return float
     */
    public function getPonderation()
    {
        return $this->ponderation;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return CQuizAnswer
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set hotspotCoordinates
     *
     * @param string $hotspotCoordinates
     * @return CQuizAnswer
     */
    public function setHotspotCoordinates($hotspotCoordinates)
    {
        $this->hotspotCoordinates = $hotspotCoordinates;

        return $this;
    }

    /**
     * Get hotspotCoordinates
     *
     * @return string
     */
    public function getHotspotCoordinates()
    {
        return $this->hotspotCoordinates;
    }

    /**
     * Set hotspotType
     *
     * @param string $hotspotType
     * @return CQuizAnswer
     */
    public function setHotspotType($hotspotType)
    {
        $this->hotspotType = $hotspotType;

        return $this;
    }

    /**
     * Get hotspotType
     *
     * @return string
     */
    public function getHotspotType()
    {
        return $this->hotspotType;
    }

    /**
     * Set destination
     *
     * @param string $destination
     * @return CQuizAnswer
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * Get destination
     *
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Set answerCode
     *
     * @param string $answerCode
     * @return CQuizAnswer
     */
    public function setAnswerCode($answerCode)
    {
        $this->answerCode = $answerCode;

        return $this;
    }

    /**
     * Get answerCode
     *
     * @return string
     */
    public function getAnswerCode()
    {
        return $this->answerCode;
    }
}
