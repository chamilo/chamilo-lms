<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEAttemptRecording.
 *
 * @ORM\Table(name="track_e_attempt_recording",
 *     indexes={
 *          @ORM\Index(name="exe_id", columns={"exe_id"}),
 *          @ORM\Index(name="question_id", columns={"question_id"}),
 *          @ORM\Index(name="session_id", columns={"session_id"})
 * })
 * @ORM\Entity
 */
class TrackEAttemptRecording
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="exe_id", type="integer", nullable=false)
     */
    protected $exeId;

    /**
     * @var int
     *
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    protected $questionId;

    /**
     * @var int
     *
     * @ORM\Column(name="marks", type="integer", nullable=false)
     */
    protected $marks;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="insert_date", type="datetime", nullable=false)
     */
    protected $insertDate;

    /**
     * @var int
     *
     * @ORM\Column(name="author", type="integer", nullable=false)
     */
    protected $author;

    /**
     * @var string
     *
     * @ORM\Column(name="teacher_comment", type="text", nullable=false)
     */
    protected $teacherComment;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * Set exeId.
     *
     * @param int $exeId
     *
     * @return TrackEAttemptRecording
     */
    public function setExeId($exeId)
    {
        $this->exeId = $exeId;

        return $this;
    }

    /**
     * Get exeId.
     *
     * @return int
     */
    public function getExeId()
    {
        return $this->exeId;
    }

    /**
     * Set questionId.
     *
     * @param int $questionId
     *
     * @return TrackEAttemptRecording
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;

        return $this;
    }

    /**
     * Get questionId.
     *
     * @return int
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * Set marks.
     *
     * @param int $marks
     *
     * @return TrackEAttemptRecording
     */
    public function setMarks($marks)
    {
        $this->marks = $marks;

        return $this;
    }

    /**
     * Get marks.
     *
     * @return int
     */
    public function getMarks()
    {
        return $this->marks;
    }

    /**
     * Set insertDate.
     *
     * @param \DateTime $insertDate
     *
     * @return TrackEAttemptRecording
     */
    public function setInsertDate($insertDate)
    {
        $this->insertDate = $insertDate;

        return $this;
    }

    /**
     * Get insertDate.
     *
     * @return \DateTime
     */
    public function getInsertDate()
    {
        return $this->insertDate;
    }

    /**
     * Set author.
     *
     * @param int $author
     *
     * @return TrackEAttemptRecording
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author.
     *
     * @return int
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set teacherComment.
     *
     * @param string $teacherComment
     *
     * @return TrackEAttemptRecording
     */
    public function setTeacherComment($teacherComment)
    {
        $this->teacherComment = $teacherComment;

        return $this;
    }

    /**
     * Get teacherComment.
     *
     * @return string
     */
    public function getTeacherComment()
    {
        return $this->teacherComment;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return TrackEAttemptRecording
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
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
}
