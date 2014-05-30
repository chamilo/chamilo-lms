<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackExerciseAttemptJury
 *
 * @ORM\Table(name="track_attempt_jury")
 * @ORM\Entity
 */
class TrackExerciseAttemptJury
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $exeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $questionId;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $score;

    /**
     * @var integer
     *
     * @ORM\Column(name="jury_member_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $juryMemberId;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_score_name_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $questionScoreNameId;

    /**
     * @ORM\ManyToOne(targetEntity="TrackExercise", inversedBy="juryAttempts")
     * @ORM\JoinColumn(name="exe_id", referencedColumnName="exe_id")
     */
    private $attempt;

    public function getAttempt()
    {
        return $this->attempt;
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
     * Set exeId
     *
     * @param integer $exeId
     * @return TrackExerciseAttemptJury
     */
    public function setExeId($exeId)
    {
        $this->exeId = $exeId;

        return $this;
    }

    /**
     * Get exeId
     *
     * @return integer
     */
    public function getExeId()
    {
        return $this->exeId;
    }

    /**
     * Set questionId
     *
     * @param integer $questionId
     * @return TrackExerciseAttemptJury
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
     * Set score
     *
     * @param float $score
     * @return TrackExerciseAttemptJury
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set juryMemberId
     *
     * @param integer $juryMemberId
     * @return TrackExerciseAttemptJury
     */
    public function setJuryMemberId($juryMemberId)
    {
        $this->juryMemberId = $juryMemberId;

        return $this;
    }

    /**
     * Get juryMemberId
     *
     * @return integer
     */
    public function getJuryMemberId()
    {
        return $this->juryMemberId;
    }

    /**
     * Set questionScoreNameId
     *
     * @param integer $questionScoreNameId
     * @return TrackExerciseAttemptJury
     */
    public function setQuestionScoreNameId($questionScoreNameId)
    {
        $this->questionScoreNameId = $questionScoreNameId;

        return $this;
    }

    /**
     * Get questionScoreNameId
     *
     * @return integer
     */
    public function getQuestionScoreNameId()
    {
        return $this->questionScoreNameId;
    }
}
