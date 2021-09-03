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
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CSurvey.
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(
 *     name="c_survey",
 *     indexes={
 *         @ORM\Index(name="idx_survey_code", columns={"code"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Repository\CSurveyRepository")
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
     * @ORM\Column(name="code", type="string", length=20, nullable=true)
     */
    #[Assert\NotBlank]
    protected ?string $code = null;

    /**
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    #[Assert\NotBlank]
    protected string $title;

    /**
     * @ORM\Column(name="subtitle", type="text", nullable=true)
     */
    protected ?string $subtitle;

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
    #[Assert\NotBlank]
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
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", nullable=true, unique=false)
     */
    protected ?int $lft = null;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", nullable=true, unique=false)
     */
    protected ?int $rgt = null;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true, unique=false)
     */
    protected ?int $lvl = null;

    /**
     * @var Collection|CSurveyQuestion[]
     *
     * @ORM\OneToMany(targetEntity="CSurveyQuestion", mappedBy="survey", cascade={"remove"})
     */
    protected Collection $questions;

    /**
     * @var Collection|CSurveyInvitation[]
     *
     * @ORM\OneToMany(targetEntity="CSurveyInvitation", mappedBy="survey", cascade={"remove"})
     */
    protected Collection $invitations;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="CSurvey", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected ?CSurvey $surveyParent = null;

    /**
     * @var Collection|CSurvey[]
     *
     * @ORM\OneToMany(targetEntity="CSurvey", mappedBy="surveyParent")
     */
    protected Collection $children;

    /**
     * @var Collection|CSurveyQuestionOption[]
     *
     * @ORM\OrderBy({"sort"="ASC"})
     * @ORM\OneToMany(targetEntity="CSurveyQuestionOption", mappedBy="survey", cascade={"remove"})
     */
    protected Collection $options;

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
     * @ORM\Column(name="visible_results", type="integer", nullable=true)
     */
    protected ?int $visibleResults = null;

    /**
     * @ORM\Column(name="is_mandatory", type="boolean", options={"default":false})
     */
    protected bool $isMandatory = false;

    public function __construct()
    {
        $this->creationDate = new DateTime();
        $this->invited = 0;
        $this->answered = 0;
        $this->anonymous = '0';
        $this->formFields = '0';
        $this->subtitle = '';
        $this->inviteMail = '';
        $this->lang = '';
        $this->reminderMail = '';
        $this->mailSubject = '';
        $this->shuffle = false;
        $this->oneQuestionPerPage = false;
        $this->surveyVersion = '';
        $this->surveyType = 0;
        $this->showFormProfile = 0;
        $this->questions = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->options = new ArrayCollection();
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

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
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

    public function setLang(string $lang): self
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

    public function setAvailFrom(DateTime $availFrom): self
    {
        $this->availFrom = $availFrom;

        return $this;
    }

    public function getAvailFrom(): ?DateTime
    {
        return $this->availFrom;
    }

    public function setAvailTill(DateTime $availTill): self
    {
        $this->availTill = $availTill;

        return $this;
    }

    public function getAvailTill(): ?DateTime
    {
        return $this->availTill;
    }

    public function setIsShared(string $isShared): self
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

    public function setTemplate(string $template): self
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

    public function setIntro(string $intro): self
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

    public function setSurveythanks(string $surveythanks): self
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

    public function setCreationDate(DateTime $creationDate): self
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

    public function setInvited(int $invited): self
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

    public function setAnswered(int $answered): self
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

    public function setInviteMail(string $inviteMail): self
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

    public function setReminderMail(string $reminderMail): self
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

    public function setMailSubject(string $mailSubject): self
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

    public function setAnonymous(string $anonymous): self
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

    public function setAccessCondition(string $accessCondition): self
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

    public function setShuffle(bool $shuffle): self
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

    public function setOneQuestionPerPage(bool $oneQuestionPerPage): self
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

    public function setSurveyVersion(string $surveyVersion): self
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

    public function setSurveyType(int $surveyType): self
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

    public function setShowFormProfile(int $showFormProfile): self
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

    public function setFormFields(string $formFields): self
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

    public function setVisibleResults(int $visibleResults): self
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

    public function setIsMandatory(bool $isMandatory): self
    {
        $this->isMandatory = $isMandatory;

        return $this;
    }

    public function isMandatory(): bool
    {
        return $this->isMandatory;
    }

    /**
     * @return CSurveyQuestion[]|Collection
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    public function setQuestions(Collection $questions): self
    {
        $this->questions = $questions;

        return $this;
    }

    public function getSurveyParent(): ?self
    {
        return $this->surveyParent;
    }

    public function setSurveyParent(?self $surveyParent): self
    {
        $this->surveyParent = $surveyParent;

        return $this;
    }

    public function getLft(): ?int
    {
        return $this->lft;
    }

    public function setLft(?int $lft): self
    {
        $this->lft = $lft;

        return $this;
    }

    public function getRgt(): ?int
    {
        return $this->rgt;
    }

    public function setRgt(?int $rgt): self
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getLvl(): ?int
    {
        return $this->lvl;
    }

    public function setLvl(?int $lvl): self
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * @return CSurvey[]|Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param CSurvey[]|Collection $children
     *
     * @return CSurvey
     */
    public function setChildren(Collection $children): self
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return CSurveyQuestionOption[]|Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(Collection $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return CSurveyInvitation[]|Collection
     */
    public function getInvitations()
    {
        return $this->invitations;
    }

    /**
     * @param CSurveyInvitation[]|Collection $invitations
     */
    public function setInvitations($invitations): self
    {
        $this->invitations = $invitations;

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
