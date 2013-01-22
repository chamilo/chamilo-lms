<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackEAttemptRecording
 *
 * @Table(name="track_e_attempt_recording")
 * @Entity
 */
class EntityTrackEAttemptRecording
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="exe_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeId;

    /**
     * @var integer
     *
     * @Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;

    /**
     * @var integer
     *
     * @Column(name="marks", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $marks;

    /**
     * @var \DateTime
     *
     * @Column(name="insert_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $insertDate;

    /**
     * @var integer
     *
     * @Column(name="author", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $author;

    /**
     * @var string
     *
     * @Column(name="teacher_comment", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $teacherComment;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


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
     * @return EntityTrackEAttemptRecording
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
     * @return EntityTrackEAttemptRecording
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
     * @return EntityTrackEAttemptRecording
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
     * @return EntityTrackEAttemptRecording
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
     * @return EntityTrackEAttemptRecording
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
     * @return EntityTrackEAttemptRecording
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
     * @return EntityTrackEAttemptRecording
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
}
