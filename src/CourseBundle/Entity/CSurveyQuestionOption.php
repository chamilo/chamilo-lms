<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="c_survey_question_option",
 *     indexes={
 *         @ORM\Index(name="idx_survey_qo_qid", columns={"question_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CSurveyQuestionOption
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="CSurveyQuestion", inversedBy="options")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CSurveyQuestion $question;

    /**
     * @ORM\ManyToOne(targetEntity="CSurvey", inversedBy="options")
     * @ORM\JoinColumn(name="survey_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CSurvey $survey;

    /**
     * @ORM\Column(name="option_text", type="text", nullable=false)
     */
    protected string $optionText;

    /**
     * @ORM\Column(name="sort", type="integer", nullable=false)
     */
    protected int $sort;

    /**
     * @ORM\Column(name="value", type="integer", nullable=false)
     */
    protected int $value;

    public function __construct()
    {
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setOptionText(string $optionText): self
    {
        $this->optionText = $optionText;

        return $this;
    }

    /**
     * Get optionText.
     *
     * @return string
     */
    public function getOptionText()
    {
        return $this->optionText;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort.
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getQuestion(): CSurveyQuestion
    {
        return $this->question;
    }

    public function setQuestion(CSurveyQuestion $question): self
    {
        $this->question = $question;

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
}
