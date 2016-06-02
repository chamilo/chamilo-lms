<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEAttemptRecording
 *
 * @ORM\Table(name="track_e_attempt_recording", indexes={@ORM\Index(name="exe_id", columns={"exe_id"}), @ORM\Index(name="question_id", columns={"question_id"}), @ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity
 */
class TrackEAttemptRecording
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_id", type="integer", nullable=false)
     */
    private $exeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    private $questionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="marks", type="integer", nullable=false)
     */
    private $marks;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="insert_date", type="datetime", nullable=false)
     */
    private $insertDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="author", type="integer", nullable=false)
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column(name="teacher_comment", type="text", nullable=false)
     */
    private $teacherComment;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * Set exeId
     *
     * @param integer $exeId
     * @return TrackEAttemptRecording
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
     * @return TrackEAttemptRecording
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
     * Set marks
     *
     * @param integer $marks
     * @return TrackEAttemptRecording
     */
    public function setMarks($marks)
    {
        $this->marks = $marks;

        return $this;
    }

    /**
     * Get marks
     *
     * @return integer
     */
    public function getMarks()
    {
        return $this->marks;
    }

    /**
     * Set insertDate
     *
     * @param \DateTime $insertDate
     * @return TrackEAttemptRecording
     */
    public function setInsertDate($insertDate)
    {
        $this->insertDate = $insertDate;

        return $this;
    }

    /**
     * Get insertDate
     *
     * @return \DateTime
     */
    public function getInsertDate()
    {
        return $this->insertDate;
    }

    /**
     * Set author
     *
     * @param integer $author
     * @return TrackEAttemptRecording
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return integer
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set teacherComment
     *
     * @param string $teacherComment
     * @return TrackEAttemptRecording
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
     * Set sessionId
     *
     * @param integer $sessionId
     * @return TrackEAttemptRecording
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
