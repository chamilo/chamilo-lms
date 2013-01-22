<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCSurveyInvitation
 *
 * @Table(name="c_survey_invitation")
 * @Entity
 */
class EntityCSurveyInvitation
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="survey_invitation_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $surveyInvitationId;

    /**
     * @var string
     *
     * @Column(name="survey_code", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyCode;

    /**
     * @var string
     *
     * @Column(name="user", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $user;

    /**
     * @var string
     *
     * @Column(name="invitation_code", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $invitationCode;

    /**
     * @var \DateTime
     *
     * @Column(name="invitation_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $invitationDate;

    /**
     * @var \DateTime
     *
     * @Column(name="reminder_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $reminderDate;

    /**
     * @var integer
     *
     * @Column(name="answered", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $answered;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCSurveyInvitation
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
     * Set surveyInvitationId
     *
     * @param integer $surveyInvitationId
     * @return EntityCSurveyInvitation
     */
    public function setSurveyInvitationId($surveyInvitationId)
    {
        $this->surveyInvitationId = $surveyInvitationId;

        return $this;
    }

    /**
     * Get surveyInvitationId
     *
     * @return integer 
     */
    public function getSurveyInvitationId()
    {
        return $this->surveyInvitationId;
    }

    /**
     * Set surveyCode
     *
     * @param string $surveyCode
     * @return EntityCSurveyInvitation
     */
    public function setSurveyCode($surveyCode)
    {
        $this->surveyCode = $surveyCode;

        return $this;
    }

    /**
     * Get surveyCode
     *
     * @return string 
     */
    public function getSurveyCode()
    {
        return $this->surveyCode;
    }

    /**
     * Set user
     *
     * @param string $user
     * @return EntityCSurveyInvitation
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return string 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set invitationCode
     *
     * @param string $invitationCode
     * @return EntityCSurveyInvitation
     */
    public function setInvitationCode($invitationCode)
    {
        $this->invitationCode = $invitationCode;

        return $this;
    }

    /**
     * Get invitationCode
     *
     * @return string 
     */
    public function getInvitationCode()
    {
        return $this->invitationCode;
    }

    /**
     * Set invitationDate
     *
     * @param \DateTime $invitationDate
     * @return EntityCSurveyInvitation
     */
    public function setInvitationDate($invitationDate)
    {
        $this->invitationDate = $invitationDate;

        return $this;
    }

    /**
     * Get invitationDate
     *
     * @return \DateTime 
     */
    public function getInvitationDate()
    {
        return $this->invitationDate;
    }

    /**
     * Set reminderDate
     *
     * @param \DateTime $reminderDate
     * @return EntityCSurveyInvitation
     */
    public function setReminderDate($reminderDate)
    {
        $this->reminderDate = $reminderDate;

        return $this;
    }

    /**
     * Get reminderDate
     *
     * @return \DateTime 
     */
    public function getReminderDate()
    {
        return $this->reminderDate;
    }

    /**
     * Set answered
     *
     * @param integer $answered
     * @return EntityCSurveyInvitation
     */
    public function setAnswered($answered)
    {
        $this->answered = $answered;

        return $this;
    }

    /**
     * Get answered
     *
     * @return integer 
     */
    public function getAnswered()
    {
        return $this->answered;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCSurveyInvitation
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
