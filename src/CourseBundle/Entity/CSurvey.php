<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_survey')]
#[ORM\Index(columns: ['code'], name: 'idx_survey_code')]
#[Gedmo\Tree(type: 'nested')]
#[ORM\Entity(repositoryClass: CSurveyRepository::class)]
class CSurvey extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'code', type: 'string', length: 40, nullable: true)]
    protected ?string $code = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text', nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'subtitle', type: 'text', nullable: true)]
    protected ?string $subtitle;

    #[ORM\Column(name: 'lang', type: 'string', length: 20, nullable: true)]
    protected ?string $lang;

    #[ORM\Column(name: 'avail_from', type: 'datetime', nullable: true)]
    protected ?DateTime $availFrom = null;

    #[ORM\Column(name: 'avail_till', type: 'datetime', nullable: true)]
    protected ?DateTime $availTill = null;

    #[ORM\Column(name: 'is_shared', type: 'string', length: 1, nullable: true)]
    protected ?string $isShared = null;

    #[ORM\Column(name: 'template', type: 'string', length: 20, nullable: true)]
    protected ?string $template = null;

    #[ORM\Column(name: 'intro', type: 'text', nullable: true)]
    protected ?string $intro = null;

    #[ORM\Column(name: 'surveythanks', type: 'text', nullable: true)]
    protected ?string $surveyThanks = null;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    protected DateTime $creationDate;

    #[ORM\Column(name: 'invited', type: 'integer', nullable: false)]
    protected int $invited;

    #[ORM\Column(name: 'answered', type: 'integer', nullable: false)]
    protected int $answered;

    #[ORM\Column(name: 'invite_mail', type: 'text', nullable: false)]
    protected string $inviteMail;

    #[ORM\Column(name: 'reminder_mail', type: 'text', nullable: false)]
    protected string $reminderMail;

    #[ORM\Column(name: 'mail_subject', type: 'string', length: 255, nullable: false)]
    protected string $mailSubject;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'anonymous', type: 'string', length: 10, nullable: false)]
    protected string $anonymous;

    #[ORM\Column(name: 'access_condition', type: 'text', nullable: true)]
    protected ?string $accessCondition = null;

    #[ORM\Column(name: 'shuffle', type: 'boolean', nullable: false)]
    protected bool $shuffle;

    #[ORM\Column(name: 'one_question_per_page', type: 'boolean', nullable: false)]
    protected bool $oneQuestionPerPage;

    #[ORM\Column(name: 'survey_version', type: 'string', length: 255, nullable: false)]
    protected string $surveyVersion;

    #[Gedmo\TreeLeft]
    #[ORM\Column(name: 'lft', type: 'integer', unique: false, nullable: true)]
    protected ?int $lft = null;

    #[Gedmo\TreeRight]
    #[ORM\Column(name: 'rgt', type: 'integer', unique: false, nullable: true)]
    protected ?int $rgt = null;

    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: 'integer', unique: false, nullable: true)]
    protected ?int $lvl = null;

    /**
     * @var Collection<int, CSurveyQuestion>
     */
    #[ORM\OneToMany(mappedBy: 'survey', targetEntity: CSurveyQuestion::class, cascade: ['remove'])]
    protected Collection $questions;

    /**
     * @var Collection<int, CSurveyInvitation>
     */
    #[ORM\OneToMany(mappedBy: 'survey', targetEntity: CSurveyInvitation::class, cascade: ['remove'])]
    protected Collection $invitations;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CSurvey $surveyParent = null;

    /**
     * @var Collection<int, CSurvey>
     */
    #[ORM\OneToMany(mappedBy: 'surveyParent', targetEntity: self::class)]
    protected Collection $children;

    /**
     * @var Collection<int, CSurveyQuestionOption>
     */
    #[ORM\OrderBy(['sort' => 'ASC'])]
    #[ORM\OneToMany(mappedBy: 'survey', targetEntity: CSurveyQuestionOption::class, cascade: ['remove'])]
    protected Collection $options;

    #[ORM\Column(name: 'survey_type', type: 'integer', nullable: false)]
    protected int $surveyType;

    #[ORM\Column(name: 'show_form_profile', type: 'integer', nullable: false)]
    protected int $showFormProfile;

    #[ORM\Column(name: 'form_fields', type: 'text', nullable: false)]
    protected string $formFields;

    #[ORM\Column(name: 'visible_results', type: 'integer', nullable: true)]
    protected ?int $visibleResults = null;

    #[ORM\Column(name: 'is_mandatory', type: 'boolean', options: ['default' => false])]
    protected bool $isMandatory = false;

    #[ORM\Column(name: 'display_question_number', type: 'boolean', options: ['default' => true])]
    protected bool $displayQuestionNumber;

    #[ORM\Column(name: 'duration', type: 'integer', nullable: true)]
    protected ?int $duration = null;

    public function __construct()
    {
        $this->title = '';
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
        $this->displayQuestionNumber = true;
    }

    public function __toString(): string
    {
        return (string) $this->getCode();
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $subtitle): self
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function getAvailFrom(): ?DateTime
    {
        return $this->availFrom;
    }

    public function setAvailFrom(?DateTime $availFrom = null): self
    {
        if (null === $availFrom) {
            $availFrom = new DateTime();
        }
        $this->availFrom = $availFrom;

        return $this;
    }

    public function getAvailTill(): ?DateTime
    {
        return $this->availTill;
    }

    public function setAvailTill(?DateTime $availTill = null): self
    {
        if (null === $availTill) {
            $availTill = new DateTime();
        }
        $this->availTill = $availTill;

        return $this;
    }

    public function getIsShared(): ?string
    {
        return $this->isShared;
    }

    public function setIsShared(string $isShared): self
    {
        $this->isShared = $isShared;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getIntro(): ?string
    {
        return $this->intro;
    }

    public function setIntro(string $intro): self
    {
        $this->intro = $intro;

        return $this;
    }

    public function getSurveythanks(): ?string
    {
        return $this->surveyThanks;
    }

    public function setSurveythanks(string $surveythanks): self
    {
        $this->surveyThanks = $surveythanks;

        return $this;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(DateTime $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getInvited(): int
    {
        return $this->invited;
    }

    public function setInvited(int $invited): self
    {
        $this->invited = $invited;

        return $this;
    }

    public function getAnswered(): int
    {
        return $this->answered;
    }

    public function setAnswered(int $answered): self
    {
        $this->answered = $answered;

        return $this;
    }

    public function getInviteMail(): string
    {
        return $this->inviteMail;
    }

    public function setInviteMail(string $inviteMail): self
    {
        $this->inviteMail = $inviteMail;

        return $this;
    }

    public function getReminderMail(): string
    {
        return $this->reminderMail;
    }

    public function setReminderMail(string $reminderMail): self
    {
        $this->reminderMail = $reminderMail;

        return $this;
    }

    public function getMailSubject(): string
    {
        return $this->mailSubject;
    }

    public function setMailSubject(string $mailSubject): self
    {
        $this->mailSubject = $mailSubject;

        return $this;
    }

    public function getAnonymous(): string
    {
        return $this->anonymous;
    }

    public function setAnonymous(string $anonymous): self
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    public function getAccessCondition(): ?string
    {
        return $this->accessCondition;
    }

    public function setAccessCondition(string $accessCondition): self
    {
        $this->accessCondition = $accessCondition;

        return $this;
    }

    public function getShuffle(): bool
    {
        return $this->shuffle;
    }

    public function setShuffle(bool $shuffle): self
    {
        $this->shuffle = $shuffle;

        return $this;
    }

    public function getOneQuestionPerPage(): bool
    {
        return $this->oneQuestionPerPage;
    }

    public function setOneQuestionPerPage(bool $oneQuestionPerPage): self
    {
        $this->oneQuestionPerPage = $oneQuestionPerPage;

        return $this;
    }

    public function getSurveyVersion(): string
    {
        return $this->surveyVersion;
    }

    public function setSurveyVersion(string $surveyVersion): self
    {
        $this->surveyVersion = $surveyVersion;

        return $this;
    }

    public function getSurveyType(): int
    {
        return $this->surveyType;
    }

    public function setSurveyType(int $surveyType): self
    {
        $this->surveyType = $surveyType;

        return $this;
    }

    public function getShowFormProfile(): int
    {
        return $this->showFormProfile;
    }

    public function setShowFormProfile(int $showFormProfile): self
    {
        $this->showFormProfile = $showFormProfile;

        return $this;
    }

    public function getFormFields(): string
    {
        return $this->formFields;
    }

    public function setFormFields(string $formFields): self
    {
        $this->formFields = $formFields;

        return $this;
    }

    public function getVisibleResults(): ?int
    {
        return $this->visibleResults;
    }

    public function setVisibleResults(int $visibleResults): self
    {
        $this->visibleResults = $visibleResults;

        return $this;
    }

    public function isMandatory(): bool
    {
        return $this->isMandatory;
    }

    public function setIsMandatory(bool $isMandatory): self
    {
        $this->isMandatory = $isMandatory;

        return $this;
    }

    /**
     * @return Collection<int, CSurveyQuestion>
     */
    public function getQuestions(): Collection
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
     * @return Collection<int, CSurvey>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param Collection<int, CSurvey> $children
     */
    public function setChildren(Collection $children): self
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return Collection<int, CSurveyQuestionOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function setOptions(Collection $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return Collection<int, CSurveyInvitation>
     */
    public function getInvitations(): Collection
    {
        return $this->invitations;
    }

    public function setInvitations(Collection $invitations): self
    {
        $this->invitations = $invitations;

        return $this;
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return (int) $this->getIid();
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getResourceName(): string
    {
        return (string) $this->getCode();
    }

    public function setResourceName(string $name): self
    {
        return $this->setCode($name);
    }

    public function isDisplayQuestionNumber(): bool
    {
        return $this->displayQuestionNumber;
    }

    public function setDisplayQuestionNumber(bool $displayQuestionNumber): static
    {
        $this->displayQuestionNumber = $displayQuestionNumber;

        return $this;
    }
}
