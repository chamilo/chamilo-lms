<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_survey_template_question_option')]
#[ORM\Index(columns: ['template_question_id'], name: 'IDX_CSTQO_QUESTION')]
#[ORM\Index(columns: ['template_question_id', 'sort'], name: 'idx_survey_template_option_sort')]
#[ORM\Entity]
class CSurveyTemplateQuestionOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CSurveyTemplateQuestion::class, inversedBy: 'options')]
    #[ORM\JoinColumn(name: 'template_question_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private CSurveyTemplateQuestion $question;

    #[ORM\Column(name: 'option_text', type: 'text')]
    private string $optionText = '';

    #[ORM\Column(type: 'integer')]
    private int $sort = 0;

    #[ORM\Column(type: 'integer')]
    private int $value = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): CSurveyTemplateQuestion
    {
        return $this->question;
    }

    public function setQuestion(CSurveyTemplateQuestion $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getOptionText(): string
    {
        return $this->optionText;
    }

    public function setOptionText(string $optionText): self
    {
        $this->optionText = $optionText;

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

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }
}
