<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurveyAnswer.
 *
 * @ORM\Table(
 *     name="c_survey_answer",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
class CSurveyAnswer
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="CSurvey")
     * @ORM\JoinColumn(name="survey_id", referencedColumnName="iid")
     */
    protected CSurvey $survey;

    /**
     * @ORM\ManyToOne(targetEntity="CSurveyQuestion", inversedBy="answers")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="iid")
     */
    protected CSurveyQuestion $question;

    /**
     * @ORM\ManyToOne(targetEntity="CSurveyQuestionOption")
     * @ORM\JoinColumn(name="option_id", referencedColumnName="iid")
     */
    protected CSurveyQuestionOption $option;

    /**
     * @ORM\Column(name="value", type="integer", nullable=false)
     */
    protected int $value;

    /**
     * @ORM\Column(name="user", type="string", length=250, nullable=false)
     */
    protected string $user;

    public function __construct()
    {
    }

    public function getIid(): int
    {
        return $this->iid;
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

    public function getSurvey(): CSurvey
    {
        return $this->survey;
    }

    public function setSurvey(CSurvey $survey): self
    {
        $this->survey = $survey;

        return $this;
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

    public function getOption(): CSurveyQuestionOption
    {
        return $this->option;
    }

    public function setOption(CSurveyQuestionOption $option): self
    {
        $this->option = $option;

        return $this;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }
}
