<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * TrackEAttemptRecording.
 *
 * @ORM\Table(name="track_e_attempt_recording",
 *     indexes={
 *         @ORM\Index(name="exe_id", columns={"exe_id"}),
 *         @ORM\Index(name="question_id", columns={"question_id"}),
 *         @ORM\Index(name="session_id", columns={"session_id"})
 *     })
 *     @ORM\Entity
 */
class TrackEAttemptRecording
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="exe_id", type="integer", nullable=false)
     */
    protected int $exeId;

    /**
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    protected int $questionId;

    /**
     * @ORM\Column(name="marks", type="integer", nullable=false)
     */
    protected int $marks;

    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="insert_date", type="datetime", nullable=false)
     */
    protected DateTime $insertDate;

    /**
     * @ORM\Column(name="author", type="integer", nullable=false)
     */
    protected int $author;

    /**
     * @ORM\Column(name="teacher_comment", type="text", nullable=false)
     */
    protected string $teacherComment;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="answer", type="text", nullable=true)
     */
    protected ?string $answer;

    public function __construct()
    {
        $this->teacherComment = '';
        $this->answer = null;
        $this->sessionId = 0;
        $this->author = 0;
    }

    /**
     * Set exeId.
     *
     * @return TrackEAttemptRecording
     */
    public function setExeId(int $exeId)
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
     * @return TrackEAttemptRecording
     */
    public function setQuestionId(int $questionId)
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

    public function setMarks(int $marks): self
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

    public function setInsertDate(DateTime $insertDate): self
    {
        $this->insertDate = $insertDate;

        return $this;
    }

    /**
     * Get insertDate.
     *
     * @return DateTime
     */
    public function getInsertDate()
    {
        return $this->insertDate;
    }

    /**
     * Set author.
     *
     * @return TrackEAttemptRecording
     */
    public function setAuthor(int $author)
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

    public function setTeacherComment(string $teacherComment): self
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
     * @return TrackEAttemptRecording
     */
    public function setSessionId(int $sessionId)
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

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }
}
