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
 *         @ORM\Index(name="course", columns={"c_id"})
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
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

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

    /**
     * Set value.
     *
     * @return CSurveyAnswer
     */
    public function setValue(int $value)
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

    /**
     * @return CSurveyAnswer
     */
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

    /**
     * Set user.
     *
     * @return CSurveyAnswer
     */
    public function setUser(string $user)
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

    /**
     * Set cId.
     *
     * @return CSurveyAnswer
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
}
