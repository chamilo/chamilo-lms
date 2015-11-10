<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurvey
 *
 * @ORM\Table(
 *  name="c_survey",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CSurvey
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_id", type="integer")
     */
    private $surveyId;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=20, nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="subtitle", type="text", nullable=true)
     */
    private $subtitle;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=20, nullable=true)
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column(name="lang", type="string", length=20, nullable=true)
     */
    private $lang;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="avail_from", type="date", nullable=true)
     */
    private $availFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="avail_till", type="date", nullable=true)
     */
    private $availTill;

    /**
     * @var string
     *
     * @ORM\Column(name="is_shared", type="string", length=1, nullable=true)
     */
    private $isShared;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=20, nullable=true)
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="intro", type="text", nullable=true)
     */
    private $intro;

    /**
     * @var string
     *
     * @ORM\Column(name="surveythanks", type="text", nullable=true)
     */
    private $surveythanks;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="invited", type="integer", nullable=false)
     */
    private $invited;

    /**
     * @var integer
     *
     * @ORM\Column(name="answered", type="integer", nullable=false)
     */
    private $answered;

    /**
     * @var string
     *
     * @ORM\Column(name="invite_mail", type="text", nullable=false)
     */
    private $inviteMail;

    /**
     * @var string
     *
     * @ORM\Column(name="reminder_mail", type="text", nullable=false)
     */
    private $reminderMail;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_subject", type="string", length=255, nullable=false)
     */
    private $mailSubject;

    /**
     * @var string
     *
     * @ORM\Column(name="anonymous", type="string", length=10, nullable=false)
     */
    private $anonymous;

    /**
     * @var string
     *
     * @ORM\Column(name="access_condition", type="text", nullable=true)
     */
    private $accessCondition;

    /**
     * @var boolean
     *
     * @ORM\Column(name="shuffle", type="boolean", nullable=false)
     */
    private $shuffle;

    /**
     * @var boolean
     *
     * @ORM\Column(name="one_question_per_page", type="boolean", nullable=false)
     */
    private $oneQuestionPerPage;

    /**
     * @var string
     *
     * @ORM\Column(name="survey_version", type="string", length=255, nullable=false)
     */
    private $surveyVersion;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_type", type="integer", nullable=false)
     */
    private $surveyType;

    /**
     * @var integer
     *
     * @ORM\Column(name="show_form_profile", type="integer", nullable=false)
     */
    private $showFormProfile;

    /**
     * @var string
     *
     * @ORM\Column(name="form_fields", type="text", nullable=false)
     */
    private $formFields;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="visible_results", type="integer", nullable=true)
     */
    private $visibleResults;

    /**
     * Set code
     *
     * @param string $code
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * @return CSurvey
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
     * Set visibleResults
     *
     * @param integer $visibleResults
     * @return CSurvey
     */
    public function setVisibleResults($visibleResults)
    {
        $this->visibleResults = $visibleResults;

        return $this;
    }

    /**
     * Get visibleResults
     *
     * @return integer
     */
    public function getVisibleResults()
    {
        return $this->visibleResults;
    }

    /**
     * Set surveyId
     *
     * @param integer $surveyId
     * @return CSurvey
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
     * Set cId
     *
     * @param integer $cId
     * @return CSurvey
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
}
