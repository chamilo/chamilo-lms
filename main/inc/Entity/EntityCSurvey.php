<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCSurvey
 *
 * @Table(name="c_survey")
 * @Entity
 */
class EntityCSurvey
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
     * @Column(name="survey_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $surveyId;

    /**
     * @var string
     *
     * @Column(name="code", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $code;

    /**
     * @var string
     *
     * @Column(name="title", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="subtitle", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $subtitle;

    /**
     * @var string
     *
     * @Column(name="author", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $author;

    /**
     * @var string
     *
     * @Column(name="lang", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $lang;

    /**
     * @var \DateTime
     *
     * @Column(name="avail_from", type="date", precision=0, scale=0, nullable=true, unique=false)
     */
    private $availFrom;

    /**
     * @var \DateTime
     *
     * @Column(name="avail_till", type="date", precision=0, scale=0, nullable=true, unique=false)
     */
    private $availTill;

    /**
     * @var string
     *
     * @Column(name="is_shared", type="string", length=1, precision=0, scale=0, nullable=true, unique=false)
     */
    private $isShared;

    /**
     * @var string
     *
     * @Column(name="template", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $template;

    /**
     * @var string
     *
     * @Column(name="intro", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $intro;

    /**
     * @var string
     *
     * @Column(name="surveythanks", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $surveythanks;

    /**
     * @var \DateTime
     *
     * @Column(name="creation_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $creationDate;

    /**
     * @var integer
     *
     * @Column(name="invited", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $invited;

    /**
     * @var integer
     *
     * @Column(name="answered", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $answered;

    /**
     * @var string
     *
     * @Column(name="invite_mail", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $inviteMail;

    /**
     * @var string
     *
     * @Column(name="reminder_mail", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $reminderMail;

    /**
     * @var string
     *
     * @Column(name="mail_subject", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $mailSubject;

    /**
     * @var string
     *
     * @Column(name="anonymous", type="string", precision=0, scale=0, nullable=false, unique=false)
     */
    private $anonymous;

    /**
     * @var string
     *
     * @Column(name="access_condition", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $accessCondition;

    /**
     * @var boolean
     *
     * @Column(name="shuffle", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $shuffle;

    /**
     * @var boolean
     *
     * @Column(name="one_question_per_page", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $oneQuestionPerPage;

    /**
     * @var string
     *
     * @Column(name="survey_version", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyVersion;

    /**
     * @var integer
     *
     * @Column(name="parent_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @Column(name="survey_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyType;

    /**
     * @var integer
     *
     * @Column(name="show_form_profile", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $showFormProfile;

    /**
     * @var string
     *
     * @Column(name="form_fields", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $formFields;

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
     * @return EntityCSurvey
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
     * Set surveyId
     *
     * @param integer $surveyId
     * @return EntityCSurvey
     */
    public function setSurveyId($surveyId)
    {
        $this->surveyId = $surveyId;

        return $this;
    }

    /**
     * Get surveyId
     *
     * @return integer 
     */
    public function getSurveyId()
    {
        return $this->surveyId;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return EntityCSurvey
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityCSurvey
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set subtitle
     *
     * @param string $subtitle
     * @return EntityCSurvey
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Get subtitle
     *
     * @return string 
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Set author
     *
     * @param string $author
     * @return EntityCSurvey
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string 
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set lang
     *
     * @param string $lang
     * @return EntityCSurvey
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return string 
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set availFrom
     *
     * @param \DateTime $availFrom
     * @return EntityCSurvey
     */
    public function setAvailFrom($availFrom)
    {
        $this->availFrom = $availFrom;

        return $this;
    }

    /**
     * Get availFrom
     *
     * @return \DateTime 
     */
    public function getAvailFrom()
    {
        return $this->availFrom;
    }

    /**
     * Set availTill
     *
     * @param \DateTime $availTill
     * @return EntityCSurvey
     */
    public function setAvailTill($availTill)
    {
        $this->availTill = $availTill;

        return $this;
    }

    /**
     * Get availTill
     *
     * @return \DateTime 
     */
    public function getAvailTill()
    {
        return $this->availTill;
    }

    /**
     * Set isShared
     *
     * @param string $isShared
     * @return EntityCSurvey
     */
    public function setIsShared($isShared)
    {
        $this->isShared = $isShared;

        return $this;
    }

    /**
     * Get isShared
     *
     * @return string 
     */
    public function getIsShared()
    {
        return $this->isShared;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return EntityCSurvey
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return string 
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set intro
     *
     * @param string $intro
     * @return EntityCSurvey
     */
    public function setIntro($intro)
    {
        $this->intro = $intro;

        return $this;
    }

    /**
     * Get intro
     *
     * @return string 
     */
    public function getIntro()
    {
        return $this->intro;
    }

    /**
     * Set surveythanks
     *
     * @param string $surveythanks
     * @return EntityCSurvey
     */
    public function setSurveythanks($surveythanks)
    {
        $this->surveythanks = $surveythanks;

        return $this;
    }

    /**
     * Get surveythanks
     *
     * @return string 
     */
    public function getSurveythanks()
    {
        return $this->surveythanks;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     * @return EntityCSurvey
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime 
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set invited
     *
     * @param integer $invited
     * @return EntityCSurvey
     */
    public function setInvited($invited)
    {
        $this->invited = $invited;

        return $this;
    }

    /**
     * Get invited
     *
     * @return integer 
     */
    public function getInvited()
    {
        return $this->invited;
    }

    /**
     * Set answered
     *
     * @param integer $answered
     * @return EntityCSurvey
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
     * Set inviteMail
     *
     * @param string $inviteMail
     * @return EntityCSurvey
     */
    public function setInviteMail($inviteMail)
    {
        $this->inviteMail = $inviteMail;

        return $this;
    }

    /**
     * Get inviteMail
     *
     * @return string 
     */
    public function getInviteMail()
    {
        return $this->inviteMail;
    }

    /**
     * Set reminderMail
     *
     * @param string $reminderMail
     * @return EntityCSurvey
     */
    public function setReminderMail($reminderMail)
    {
        $this->reminderMail = $reminderMail;

        return $this;
    }

    /**
     * Get reminderMail
     *
     * @return string 
     */
    public function getReminderMail()
    {
        return $this->reminderMail;
    }

    /**
     * Set mailSubject
     *
     * @param string $mailSubject
     * @return EntityCSurvey
     */
    public function setMailSubject($mailSubject)
    {
        $this->mailSubject = $mailSubject;

        return $this;
    }

    /**
     * Get mailSubject
     *
     * @return string 
     */
    public function getMailSubject()
    {
        return $this->mailSubject;
    }

    /**
     * Set anonymous
     *
     * @param string $anonymous
     * @return EntityCSurvey
     */
    public function setAnonymous($anonymous)
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    /**
     * Get anonymous
     *
     * @return string 
     */
    public function getAnonymous()
    {
        return $this->anonymous;
    }

    /**
     * Set accessCondition
     *
     * @param string $accessCondition
     * @return EntityCSurvey
     */
    public function setAccessCondition($accessCondition)
    {
        $this->accessCondition = $accessCondition;

        return $this;
    }

    /**
     * Get accessCondition
     *
     * @return string 
     */
    public function getAccessCondition()
    {
        return $this->accessCondition;
    }

    /**
     * Set shuffle
     *
     * @param boolean $shuffle
     * @return EntityCSurvey
     */
    public function setShuffle($shuffle)
    {
        $this->shuffle = $shuffle;

        return $this;
    }

    /**
     * Get shuffle
     *
     * @return boolean 
     */
    public function getShuffle()
    {
        return $this->shuffle;
    }

    /**
     * Set oneQuestionPerPage
     *
     * @param boolean $oneQuestionPerPage
     * @return EntityCSurvey
     */
    public function setOneQuestionPerPage($oneQuestionPerPage)
    {
        $this->oneQuestionPerPage = $oneQuestionPerPage;

        return $this;
    }

    /**
     * Get oneQuestionPerPage
     *
     * @return boolean 
     */
    public function getOneQuestionPerPage()
    {
        return $this->oneQuestionPerPage;
    }

    /**
     * Set surveyVersion
     *
     * @param string $surveyVersion
     * @return EntityCSurvey
     */
    public function setSurveyVersion($surveyVersion)
    {
        $this->surveyVersion = $surveyVersion;

        return $this;
    }

    /**
     * Get surveyVersion
     *
     * @return string 
     */
    public function getSurveyVersion()
    {
        return $this->surveyVersion;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     * @return EntityCSurvey
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer 
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set surveyType
     *
     * @param integer $surveyType
     * @return EntityCSurvey
     */
    public function setSurveyType($surveyType)
    {
        $this->surveyType = $surveyType;

        return $this;
    }

    /**
     * Get surveyType
     *
     * @return integer 
     */
    public function getSurveyType()
    {
        return $this->surveyType;
    }

    /**
     * Set showFormProfile
     *
     * @param integer $showFormProfile
     * @return EntityCSurvey
     */
    public function setShowFormProfile($showFormProfile)
    {
        $this->showFormProfile = $showFormProfile;

        return $this;
    }

    /**
     * Get showFormProfile
     *
     * @return integer 
     */
    public function getShowFormProfile()
    {
        return $this->showFormProfile;
    }

    /**
     * Set formFields
     *
     * @param string $formFields
     * @return EntityCSurvey
     */
    public function setFormFields($formFields)
    {
        $this->formFields = $formFields;

        return $this;
    }

    /**
     * Get formFields
     *
     * @return string 
     */
    public function getFormFields()
    {
        return $this->formFields;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCSurvey
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
