<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CSurvey.
 *
 * @ORM\Table(
 *     name="c_survey",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="session_id", columns={"session_id"}),
 *         @ORM\Index(name="idx_survey_code", columns={"code"})
 *     }
 * )
 * @ORM\Entity
 */
class CSurvey extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="code", type="string", length=20, nullable=true)
     */
    protected ?string $code = null;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="subtitle", type="text", nullable=true)
     */
    protected ?string $subtitle;

    /**
     * @ORM\Column(name="author", type="string", length=20, nullable=true)
     */
    protected ?string $author;

    /**
     * @ORM\Column(name="lang", type="string", length=20, nullable=true)
     */
    protected ?string $lang;

    /**
     * @ORM\Column(name="avail_from", type="datetime", nullable=true)
     */
    protected ?DateTime $availFrom = null;

    /**
     * @ORM\Column(name="avail_till", type="datetime", nullable=true)
     */
    protected ?DateTime $availTill = null;

    /**
     * @ORM\Column(name="is_shared", type="string", length=1, nullable=true)
     */
    protected ?string $isShared = null;

    /**
     * @ORM\Column(name="template", type="string", length=20, nullable=true)
     */
    protected ?string $template = null;

    /**
     * @ORM\Column(name="intro", type="text", nullable=true)
     */
    protected ?string $intro = null;

    /**
     * @ORM\Column(name="surveythanks", type="text", nullable=true)
     */
    protected ?string $surveyThanks = null;

    /**
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    protected DateTime $creationDate;

    /**
     * @ORM\Column(name="invited", type="integer", nullable=false)
     */
    protected int $invited;

    /**
     * @ORM\Column(name="answered", type="integer", nullable=false)
     */
    protected int $answered;

    /**
     * @ORM\Column(name="invite_mail", type="text", nullable=false)
     */
    protected string $inviteMail;

    /**
     * @ORM\Column(name="reminder_mail", type="text", nullable=false)
     */
    protected string $reminderMail;

    /**
     * @ORM\Column(name="mail_subject", type="string", length=255, nullable=false)
     */
    protected string $mailSubject;

    /**
     * @ORM\Column(name="anonymous", type="string", length=10, nullable=false)
     */
    protected string $anonymous;

    /**
     * @ORM\Column(name="access_condition", type="text", nullable=true)
     */
    protected ?string $accessCondition = null;

    /**
     * @ORM\Column(name="shuffle", type="boolean", nullable=false)
     */
    protected bool $shuffle;

    /**
     * @ORM\Column(name="one_question_per_page", type="boolean", nullable=false)
     */
    protected bool $oneQuestionPerPage;

    /**
     * @ORM\Column(name="survey_version", type="string", length=255, nullable=false)
     */
    protected string $surveyVersion;

    /**
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    protected int $parentId;

    /**
     * @ORM\Column(name="survey_type", type="integer", nullable=false)
     */
    protected int $surveyType;

    /**
     * @ORM\Column(name="show_form_profile", type="integer", nullable=false)
     */
    protected int $showFormProfile;

    /**
     * @ORM\Column(name="form_fields", type="text", nullable=false)
     */
    protected string $formFields;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="visible_results", type="integer", nullable=true)
     */
    protected ?int $visibleResults = null;

    /**
     * @ORM\Column(name="is_mandatory", type="boolean", options={"default":false})
     */
    protected bool $isMandatory = false;

    /**
     * @var Collection|CSurveyQuestion[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CSurveyQuestion", mappedBy="survey")
     */
    protected Collection $questions;

    public function __construct()
    {
        $this->creationDate = new DateTime();
        $this->invited = 0;
        $this->answered = 0;
        $this->subtitle = '';
        $this->author = '';
        $this->inviteMail = '';
        $this->lang = '';
        $this->reminderMail = '';
        $this->mailSubject = '';
        $this->shuffle = false;
        $this->oneQuestionPerPage = false;
        $this->surveyVersion = '';
        $this->parentId = 0;
        $this->surveyType = 0;
        $this->questions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getCode();
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set title.
     *
     * @return CSurvey
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function setSubtitle(string $subtitle): self
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author.
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set lang.
     *
     * @return CSurvey
     */
    public function setLang(string $lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang.
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set availFrom.
     *
     * @return CSurvey
     */
    public function setAvailFrom(DateTime $availFrom)
    {
        $this->availFrom = $availFrom;

        return $this;
    }

    /**
     * Get availFrom.
     *
     * @return DateTime
     */
    public function getAvailFrom()
    {
        return $this->availFrom;
    }

    /**
     * Set availTill.
     *
     * @return CSurvey
     */
    public function setAvailTill(DateTime $availTill)
    {
        $this->availTill = $availTill;

        return $this;
    }

    /**
     * Get availTill.
     *
     * @return DateTime
     */
    public function getAvailTill()
    {
        return $this->availTill;
    }

    /**
     * Set isShared.
     *
     * @return CSurvey
     */
    public function setIsShared(string $isShared)
    {
        $this->isShared = $isShared;

        return $this;
    }

    /**
     * Get isShared.
     *
     * @return string
     */
    public function getIsShared()
    {
        return $this->isShared;
    }

    /**
     * Set template.
     *
     * @return CSurvey
     */
    public function setTemplate(string $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set intro.
     *
     * @return CSurvey
     */
    public function setIntro(string $intro)
    {
        $this->intro = $intro;

        return $this;
    }

    /**
     * Get intro.
     *
     * @return string
     */
    public function getIntro()
    {
        return $this->intro;
    }

    /**
     * Set surveythanks.
     *
     * @return CSurvey
     */
    public function setSurveythanks(string $surveythanks)
    {
        $this->surveyThanks = $surveythanks;

        return $this;
    }

    /**
     * Get surveythanks.
     *
     * @return string
     */
    public function getSurveythanks()
    {
        return $this->surveyThanks;
    }

    /**
     * Set creationDate.
     *
     * @return CSurvey
     */
    public function setCreationDate(DateTime $creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set invited.
     *
     * @return CSurvey
     */
    public function setInvited(int $invited)
    {
        $this->invited = $invited;

        return $this;
    }

    /**
     * Get invited.
     *
     * @return int
     */
    public function getInvited()
    {
        return $this->invited;
    }

    /**
     * Set answered.
     *
     * @return CSurvey
     */
    public function setAnswered(int $answered)
    {
        $this->answered = $answered;

        return $this;
    }

    /**
     * Get answered.
     *
     * @return int
     */
    public function getAnswered()
    {
        return $this->answered;
    }

    /**
     * Set inviteMail.
     *
     * @return CSurvey
     */
    public function setInviteMail(string $inviteMail)
    {
        $this->inviteMail = $inviteMail;

        return $this;
    }

    /**
     * Get inviteMail.
     *
     * @return string
     */
    public function getInviteMail()
    {
        return $this->inviteMail;
    }

    /**
     * Set reminderMail.
     *
     * @return CSurvey
     */
    public function setReminderMail(string $reminderMail)
    {
        $this->reminderMail = $reminderMail;

        return $this;
    }

    /**
     * Get reminderMail.
     *
     * @return string
     */
    public function getReminderMail()
    {
        return $this->reminderMail;
    }

    /**
     * Set mailSubject.
     *
     * @return CSurvey
     */
    public function setMailSubject(string $mailSubject)
    {
        $this->mailSubject = $mailSubject;

        return $this;
    }

    /**
     * Get mailSubject.
     *
     * @return string
     */
    public function getMailSubject()
    {
        return $this->mailSubject;
    }

    /**
     * Set anonymous.
     *
     * @return CSurvey
     */
    public function setAnonymous(string $anonymous)
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    /**
     * Get anonymous.
     *
     * @return string
     */
    public function getAnonymous()
    {
        return $this->anonymous;
    }

    /**
     * Set accessCondition.
     *
     * @return CSurvey
     */
    public function setAccessCondition(string $accessCondition)
    {
        $this->accessCondition = $accessCondition;

        return $this;
    }

    /**
     * Get accessCondition.
     *
     * @return string
     */
    public function getAccessCondition()
    {
        return $this->accessCondition;
    }

    /**
     * Set shuffle.
     *
     * @return CSurvey
     */
    public function setShuffle(bool $shuffle)
    {
        $this->shuffle = $shuffle;

        return $this;
    }

    /**
     * Get shuffle.
     *
     * @return bool
     */
    public function getShuffle()
    {
        return $this->shuffle;
    }

    /**
     * Set oneQuestionPerPage.
     *
     * @return CSurvey
     */
    public function setOneQuestionPerPage(bool $oneQuestionPerPage)
    {
        $this->oneQuestionPerPage = $oneQuestionPerPage;

        return $this;
    }

    /**
     * Get oneQuestionPerPage.
     *
     * @return bool
     */
    public function getOneQuestionPerPage()
    {
        return $this->oneQuestionPerPage;
    }

    /**
     * Set surveyVersion.
     *
     * @return CSurvey
     */
    public function setSurveyVersion(string $surveyVersion)
    {
        $this->surveyVersion = $surveyVersion;

        return $this;
    }

    /**
     * Get surveyVersion.
     *
     * @return string
     */
    public function getSurveyVersion()
    {
        return $this->surveyVersion;
    }

    /**
     * Set parentId.
     *
     * @return CSurvey
     */
    public function setParentId(int $parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId.
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set surveyType.
     *
     * @return CSurvey
     */
    public function setSurveyType(int $surveyType)
    {
        $this->surveyType = $surveyType;

        return $this;
    }

    /**
     * Get surveyType.
     *
     * @return int
     */
    public function getSurveyType()
    {
        return $this->surveyType;
    }

    /**
     * Set showFormProfile.
     *
     * @return CSurvey
     */
    public function setShowFormProfile(int $showFormProfile)
    {
        $this->showFormProfile = $showFormProfile;

        return $this;
    }

    /**
     * Get showFormProfile.
     *
     * @return int
     */
    public function getShowFormProfile()
    {
        return $this->showFormProfile;
    }

    /**
     * Set formFields.
     *
     * @return CSurvey
     */
    public function setFormFields(string $formFields)
    {
        $this->formFields = $formFields;

        return $this;
    }

    /**
     * Get formFields.
     *
     * @return string
     */
    public function getFormFields()
    {
        return $this->formFields;
    }

    /**
     * Set sessionId.
     *
     * @return CSurvey
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
     * Set visibleResults.
     *
     * @return CSurvey
     */
    public function setVisibleResults(int $visibleResults)
    {
        $this->visibleResults = $visibleResults;

        return $this;
    }

    /**
     * Get visibleResults.
     *
     * @return int
     */
    public function getVisibleResults()
    {
        return $this->visibleResults;
    }

    /**
     * Set cId.
     *
     * @return CSurvey
     */
    public function setCId(int $cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * @return CSurvey
     */
    public function setIsMandatory(bool $isMandatory)
    {
        $this->isMandatory = $isMandatory;

        return $this;
    }

    public function isMandatory(): bool
    {
        return $this->isMandatory;
    }

    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function setQuestions(Collection $questions): self
    {
        $this->questions = $questions;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getCode();
    }

    public function setResourceName(string $name): self
    {
        return $this->setCode($name);
    }
}
