<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_survey_template')]
#[ORM\Index(columns: ['access_url_id'], name: 'IDX_CST_ACCESS_URL')]
#[ORM\Index(columns: ['created_by_id'], name: 'IDX_CST_CREATED_BY')]
#[ORM\Index(columns: ['access_url_id', 'visible'], name: 'idx_survey_template_catalog')]
#[ORM\Entity]
class CSurveyTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AccessUrl $accessUrl;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $intro = null;

    #[ORM\Column(name: 'survey_thanks', type: 'text', nullable: true)]
    private ?string $surveyThanks = null;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => '0'])]
    private string $anonymous = '0';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $shuffle = false;

    #[ORM\Column(name: 'one_question_per_page', type: 'boolean', options: ['default' => false])]
    private bool $oneQuestionPerPage = false;

    #[ORM\Column(name: 'display_question_number', type: 'boolean', options: ['default' => true])]
    private bool $displayQuestionNumber = true;

    #[ORM\Column(name: 'is_mandatory', type: 'boolean', options: ['default' => false])]
    private bool $isMandatory = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $visible = false;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private DateTime $updatedAt;

    /**
     * @var Collection<int, CSurveyTemplateQuestion>
     */
    #[ORM\OneToMany(
        mappedBy: 'template',
        targetEntity: CSurveyTemplateQuestion::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['sort' => 'ASC', 'id' => 'ASC'])]
    private Collection $questions;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->questions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccessUrl(): AccessUrl
    {
        return $this->accessUrl;
    }

    public function setAccessUrl(AccessUrl $accessUrl): self
    {
        $this->accessUrl = $accessUrl;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

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

    public function setSubtitle(?string $subtitle): self
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getIntro(): ?string
    {
        return $this->intro;
    }

    public function setIntro(?string $intro): self
    {
        $this->intro = $intro;

        return $this;
    }

    public function getSurveyThanks(): ?string
    {
        return $this->surveyThanks;
    }

    public function setSurveyThanks(?string $surveyThanks): self
    {
        $this->surveyThanks = $surveyThanks;

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

    public function isDisplayQuestionNumber(): bool
    {
        return $this->displayQuestionNumber;
    }

    public function setDisplayQuestionNumber(bool $displayQuestionNumber): self
    {
        $this->displayQuestionNumber = $displayQuestionNumber;

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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function touch(): self
    {
        $this->updatedAt = new DateTime();

        return $this;
    }

    /**
     * @return Collection<int, CSurveyTemplateQuestion>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }
}
