<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'skill_rel_user_comment')]
#[ORM\Index(columns: ['skill_rel_user_id', 'feedback_giver_id'], name: 'idx_select_su_giver')]
#[ORM\Entity]
class SkillRelUserComment
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SkillRelUser::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'skill_rel_user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?SkillRelUser $skillRelUser = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commentedUserSkills')]
    #[ORM\JoinColumn(name: 'feedback_giver_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $feedbackGiver = null;

    #[ORM\Column(name: 'feedback_text', type: 'text')]
    protected string $feedbackText;

    #[ORM\Column(name: 'feedback_value', type: 'integer', nullable: true, options: ['default' => 1])]
    protected ?int $feedbackValue = null;

    #[ORM\Column(name: 'feedback_datetime', type: 'datetime', nullable: false)]
    protected DateTime $feedbackDateTime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSkillRelUser(): ?SkillRelUser
    {
        return $this->skillRelUser;
    }

    public function getFeedbackGiver(): ?User
    {
        return $this->feedbackGiver;
    }

    public function getFeedbackText(): string
    {
        return $this->feedbackText;
    }

    public function getFeedbackValue(): ?int
    {
        return $this->feedbackValue;
    }

    public function getFeedbackDateTime(): DateTime
    {
        return $this->feedbackDateTime;
    }

    public function setSkillRelUser(SkillRelUser $skillRelUser): self
    {
        $this->skillRelUser = $skillRelUser;

        return $this;
    }

    public function setFeedbackGiver(User $feedbackGiver): self
    {
        $this->feedbackGiver = $feedbackGiver;

        return $this;
    }

    public function setFeedbackText(string $feedbackText): self
    {
        $this->feedbackText = $feedbackText;

        return $this;
    }

    public function setFeedbackValue(?int $feedbackValue): self
    {
        $this->feedbackValue = $feedbackValue;

        return $this;
    }

    public function setFeedbackDateTime(DateTime $feedbackDateTime): self
    {
        $this->feedbackDateTime = $feedbackDateTime;

        return $this;
    }
}
