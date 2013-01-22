<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackEAttempt
 *
 * @Table(name="track_e_attempt")
 * @Entity
 */
class EntityTrackEAttempt
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
     * @Column(name="exe_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $exeId;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;

    /**
     * @var string
     *
     * @Column(name="answer", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $answer;

    /**
     * @var string
     *
     * @Column(name="teacher_comment", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $teacherComment;

    /**
     * @var float
     *
     * @Column(name="marks", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $marks;

    /**
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseCode;

    /**
     * @var integer
     *
     * @Column(name="position", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $position;

    /**
     * @var \DateTime
     *
     * @Column(name="tms", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $tms;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var string
     *
     * @Column(name="filename", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $filename;


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
     * @return EntityTrackEAttempt
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
     * @return EntityTrackEAttempt
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
     * @return EntityTrackEAttempt
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
     * @return EntityTrackEAttempt
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
     * @return EntityTrackEAttempt
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
     * @return EntityTrackEAttempt
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
     * Set courseCode
     *
     * @param string $courseCode
     * @return EntityTrackEAttempt
     */
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode
     *
     * @return string 
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return EntityTrackEAttempt
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
     * @return EntityTrackEAttempt
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
     * @return EntityTrackEAttempt
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
     * @return EntityTrackEAttempt
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
}
