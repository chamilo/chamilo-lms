<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEAttempt
 *
 * @ORM\Table(
 *  name="track_e_attempt",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="exe_id", columns={"exe_id"}),
 *      @ORM\Index(name="user_id", columns={"user_id"}),
 *      @ORM\Index(name="question_id", columns={"question_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class TrackEAttempt
{
    /**
     * @var integer
     *
     * @ORM\Column(name="exe_id", type="integer", nullable=true)
     */
    private $exeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

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
     * @var string
     *
     * @ORM\Column(name="teacher_comment", type="text", nullable=false)
     */
    private $teacherComment;

    /**
     * @var float
     *
     * @ORM\Column(name="marks", type="float", precision=6, scale=2, nullable=false)
     */
    private $marks;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    private $position;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tms", type="datetime", nullable=true)
     */
    private $tms;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255, nullable=true)
     */
    private $filename;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set exeId
     *
     * @param integer $exeId
     * @return TrackEAttempt
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
     * Set userId
     *
     * @param integer $userId
     * @return TrackEAttempt
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set questionId
     *
     * @param integer $questionId
     * @return TrackEAttempt
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
     * @return TrackEAttempt
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
     * Set teacherComment
     *
     * @param string $teacherComment
     * @return TrackEAttempt
     */
    public function setTeacherComment($teacherComment)
    {
        $this->teacherComment = $teacherComment;

        return $this;
    }

    /**
     * Get teacherComment
     *
     * @return string
     */
    public function getTeacherComment()
    {
        return $this->teacherComment;
    }

    /**
     * Set marks
     *
     * @param float $marks
     * @return TrackEAttempt
     */
    public function setMarks($marks)
    {
        $this->marks = $marks;

        return $this;
    }

    /**
     * Get marks
     *
     * @return float
     */
    public function getMarks()
    {
        return $this->marks;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return TrackEAttempt
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
     * Set position
     *
     * @param integer $position
     * @return TrackEAttempt
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
     * Set tms
     *
     * @param \DateTime $tms
     * @return TrackEAttempt
     */
    public function setTms($tms)
    {
        $this->tms = $tms;

        return $this;
    }

    /**
     * Get tms
     *
     * @return \DateTime
     */
    public function getTms()
    {
        return $this->tms;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return TrackEAttempt
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return TrackEAttempt
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
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
}
