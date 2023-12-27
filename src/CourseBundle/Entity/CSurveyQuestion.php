<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CourseBundle\Repository\CSurveyQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_survey_question')]
#[ORM\Entity(repositoryClass: CSurveyQuestionRepository::class)]
class CSurveyQuestion
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'iid', onDelete: 'SET NULL')]
    protected ?CSurveyQuestion $parent = null;

    /**
     * @var Collection<int, CSurveyQuestion>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    protected Collection $children;

    /**
     * @var Collection<int, CSurveyQuestionOption>
     */
    #[ORM\OneToMany(mappedBy: 'question', targetEntity: CSurveyQuestionOption::class, cascade: ['remove'])]
    protected Collection $options;

    #[ORM\ManyToOne(targetEntity: CSurveyQuestionOption::class, cascade: ['remove'])]
    #[ORM\JoinColumn(name: 'parent_option_id', referencedColumnName: 'iid')]
    protected ?CSurveyQuestionOption $parentOption = null;

    #[ORM\ManyToOne(targetEntity: CSurvey::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'survey_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CSurvey $survey;

    /**
     * @var Collection<int, CSurveyAnswer>
     */
    #[ORM\OneToMany(mappedBy: 'question', targetEntity: CSurveyAnswer::class, cascade: ['remove'])]
    protected Collection $answers;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'survey_question', type: 'text', nullable: false)]
    protected string $surveyQuestion;

    #[ORM\Column(name: 'survey_question_comment', type: 'text', nullable: false)]
    protected ?string $surveyQuestionComment = null;

    #[ORM\Column(name: 'type', type: 'string', length: 250, nullable: false)]
    protected string $type;

    #[ORM\Column(name: 'display', type: 'string', length: 10, nullable: false)]
    protected string $display;

    #[ORM\Column(name: 'sort', type: 'integer', nullable: false)]
    protected int $sort;

    #[ORM\Column(name: 'shared_question_id', type: 'integer', nullable: true)]
    protected ?int $sharedQuestionId = null;

    #[ORM\Column(name: 'max_value', type: 'integer', nullable: true)]
    protected ?int $maxValue = null;

    #[ORM\Column(name: 'survey_group_pri', type: 'integer', nullable: false)]
    protected int $surveyGroupPri;

    #[ORM\Column(name: 'survey_group_sec1', type: 'integer', nullable: false)]
    protected int $surveyGroupSec1;

    #[ORM\Column(name: 'survey_group_sec2', type: 'integer', nullable: false)]
    protected int $surveyGroupSec2;

    #[ORM\Column(name: 'is_required', type: 'boolean', options: ['default' => false])]
    protected bool $isMandatory = false;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->answers = new ArrayCollection();
        $this->options = new ArrayCollection();
        $this->surveyGroupPri = 0;
        $this->surveyGroupSec1 = 0;
        $this->surveyGroupSec2 = 0;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setSurveyQuestion(string $surveyQuestion): self
    {
        $this->surveyQuestion = $surveyQuestion;

        return $this;
    }

    public function getSurveyQuestion(): string
    {
        return $this->surveyQuestion;
    }

    public function setSurveyQuestionComment(string $surveyQuestionComment): self
    {
        $this->surveyQuestionComment = $surveyQuestionComment;

        return $this;
    }

    /**
     * Get surveyQuestionComment.
     */
    public function getSurveyQuestionComment(): ?string
    {
        return $this->surveyQuestionComment;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function setDisplay(string $display): self
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display.
     */
    public function getDisplay(): string
    {
        return $this->display;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort.
     */
    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSharedQuestionId(int $sharedQuestionId): self
    {
        $this->sharedQuestionId = $sharedQuestionId;

        return $this;
    }

    /**
     * Get sharedQuestionId.
     */
    public function getSharedQuestionId(): ?int
    {
        return $this->sharedQuestionId;
    }

    public function setMaxValue(int $maxValue): self
    {
        $this->maxValue = $maxValue;

        return $this;
    }

    /**
     * Get maxValue.
     */
    public function getMaxValue(): ?int
    {
        return $this->maxValue;
    }

    /**
     * Set surveyGroupPri.
     *
     * @return CSurveyQuestion
     */
    public function setSurveyGroupPri(int $surveyGroupPri): static
    {
        $this->surveyGroupPri = $surveyGroupPri;

        return $this;
    }

    /**
     * Get surveyGroupPri.
     */
    public function getSurveyGroupPri(): int
    {
        return $this->surveyGroupPri;
    }

    /**
     * Set surveyGroupSec1.
     *
     * @return CSurveyQuestion
     */
    public function setSurveyGroupSec1(int $surveyGroupSec1): static
    {
        $this->surveyGroupSec1 = $surveyGroupSec1;

        return $this;
    }

    /**
     * Get surveyGroupSec1.
     */
    public function getSurveyGroupSec1(): int
    {
        return $this->surveyGroupSec1;
    }

    /**
     * Set surveyGroupSec2.
     *
     * @return CSurveyQuestion
     */
    public function setSurveyGroupSec2(int $surveyGroupSec2): static
    {
        $this->surveyGroupSec2 = $surveyGroupSec2;

        return $this;
    }

    /**
     * Get surveyGroupSec2.
     */
    public function getSurveyGroupSec2(): int
    {
        return $this->surveyGroupSec2;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return ArrayCollection<int, CSurveyQuestion>
     */
    public function getChildren(): ArrayCollection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function getParentOption(): ?CSurveyQuestionOption
    {
        return $this->parentOption;
    }

    public function setParentOption(CSurveyQuestionOption $parentOption): self
    {
        $this->parentOption = $parentOption;

        return $this;
    }

    public function getSurvey(): CSurvey
    {
        return $this->survey;
    }

    public function setSurvey(CSurvey $survey): self
    {
        $this->survey = $survey;

        return $this;
    }

    /**
     * @return ArrayCollection<int, CSurveyAnswer>
     */
    public function getAnswers(): ArrayCollection
    {
        return $this->answers;
    }

    /**
     * @return ArrayCollection<int, CSurveyQuestionOption>
     */
    public function getOptions(): ArrayCollection
    {
        return $this->options;
    }

    public function setOptions(Collection $options): self
    {
        $this->options = $options;

        return $this;
    }
}
