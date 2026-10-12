<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_survey_template_question')]
#[ORM\Index(columns: ['template_id'], name: 'IDX_CSTQ_TEMPLATE')]
#[ORM\Index(columns: ['parent_id'], name: 'IDX_CSTQ_PARENT')]
#[ORM\Index(columns: ['parent_option_id'], name: 'IDX_CSTQ_PARENT_OPTION')]
#[ORM\Index(columns: ['template_id', 'sort'], name: 'idx_survey_template_question_sort')]
#[ORM\Entity]
class CSurveyTemplateQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CSurveyTemplate::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private CSurveyTemplate $template;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\ManyToOne(targetEntity: CSurveyTemplateQuestionOption::class)]
    #[ORM\JoinColumn(name: 'parent_option_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?CSurveyTemplateQuestionOption $parentOption = null;

    #[ORM\Column(type: 'text')]
    private string $question = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'string', length: 250)]
    private string $type = 'open';

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'vertical'])]
    private string $display = 'vertical';

    #[ORM\Column(type: 'integer')]
    private int $sort = 0;

    #[ORM\Column(name: 'max_value', type: 'integer', nullable: true)]
    private ?int $maxValue = null;

    #[ORM\Column(name: 'is_required', type: 'boolean', options: ['default' => false])]
    private bool $isRequired = false;

    /**
     * @var Collection<int, CSurveyTemplateQuestionOption>
     */
    #[ORM\OneToMany(
        mappedBy: 'question',
        targetEntity: CSurveyTemplateQuestionOption::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['sort' => 'ASC', 'id' => 'ASC'])]
    private Collection $options;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->options = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemplate(): CSurveyTemplate
    {
        return $this->template;
    }

    public function setTemplate(CSurveyTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParentOption(): ?CSurveyTemplateQuestionOption
    {
        return $this->parentOption;
    }

    public function setParentOption(?CSurveyTemplateQuestionOption $parentOption): self
    {
        $this->parentOption = $parentOption;

        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDisplay(): string
    {
        return $this->display;
    }

    public function setDisplay(string $display): self
    {
        $this->display = $display;

        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function getMaxValue(): ?int
    {
        return $this->maxValue;
    }

    public function setMaxValue(?int $maxValue): self
    {
        $this->maxValue = $maxValue;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): self
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    /**
     * @return Collection<int, CSurveyTemplateQuestionOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }
}
