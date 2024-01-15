<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_survey_answer')]
#[ORM\Entity]
class CSurveyAnswer
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\ManyToOne(targetEntity: CSurvey::class)]
    #[ORM\JoinColumn(name: 'survey_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CSurvey $survey;

    #[ORM\ManyToOne(targetEntity: CSurveyQuestion::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'iid')]
    protected CSurveyQuestion $question;

    #[ORM\Column(name: 'option_id', type: 'text', nullable: false)]
    protected string $optionId;

    #[ORM\Column(name: 'value', type: 'integer', nullable: false)]
    protected int $value;

    #[ORM\Column(name: 'user', type: 'string', length: 250, nullable: false)]
    protected string $user;

    #[ORM\Column(name: 'session_id', type: 'integer', nullable: true)]
    protected ?int $sessionId;

    #[ORM\Column(name: 'c_lp_item_id', type: 'integer', nullable: false)]
    protected int $lpItemId;

    public function __construct()
    {
        $this->lpItemId = 0;
    }

    public function getIid(): ?int
    {
        return $this->iid;
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

    public function setValue(int $value): self
    {
        $this->value = $value;

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

    public function getQuestion(): CSurveyQuestion
    {
        return $this->question;
    }

    public function setQuestion(CSurveyQuestion $question): self
    {
        $this->question = $question;

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

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getOptionId(): string
    {
        return $this->optionId;
    }

    public function setOptionId(string $optionId): self
    {
        $this->optionId = $optionId;

        return $this;
    }

    public function getSessionId(): ?int
    {
        return $this->sessionId;
    }

    public function setSessionId(int $sessionId = null): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get the lp item Id.
     *
     * @return int
     */
    public function getLpItemId()
    {
        return $this->lpItemId;
    }

    /**
     * Set lp item Id.
     *
     * @return CSurveyAnswer
     */
    public function setLpItemId(int $lpItemId)
    {
        $this->lpItemId = $lpItemId;

        return $this;
    }
}
