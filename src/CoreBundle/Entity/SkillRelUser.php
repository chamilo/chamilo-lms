<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="skill_rel_user",
 *     indexes={
 *         @ORM\Index(name="idx_select_cs", columns={"course_id", "session_id"}),
 *         @ORM\Index(name="idx_select_s_c_u", columns={"session_id", "course_id", "user_id"}),
 *         @ORM\Index(name="idx_select_sk_u", columns={"skill_id", "user_id"})
 *     }
 * )
 * @ORM\Entity
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\SkillRelUserListener"})
 */
class SkillRelUser
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="achievedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Skill", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="skill_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected ?Skill $skill = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Course $course = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Session $session = null;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SkillRelUserComment", mappedBy="skillRelUser",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     *
     * @var Collection|SkillRelUserComment[]
     */
    protected Collection $comments;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Level")
     * @ORM\JoinColumn(name="acquired_level", referencedColumnName="id")
     */
    protected ?Level $acquiredLevel = null;

    /**
     * @ORM\Column(name="acquired_skill_at", type="datetime", nullable=false)
     */
    protected DateTime $acquiredSkillAt;

    /**
     * Whether this has been confirmed by a teacher or not
     * Only set to 0 when the skill_rel_item says requires_validation = 1.
     *
     * @ORM\Column(name="validation_status", type="integer")
     */
    protected int $validationStatus;

    /**
     * @ORM\Column(name="assigned_by", type="integer", nullable=false)
     */
    #[Assert\NotBlank]
    protected int $assignedBy;

    /**
     * @ORM\Column(name="argumentation", type="text")
     */
    #[Assert\NotBlank]
    protected string $argumentation;

    /**
     * @ORM\Column(name="argumentation_author_id", type="integer")
     */
    protected int $argumentationAuthorId;

    public function __construct()
    {
        $this->validationStatus = 0;
        $this->comments = new ArrayCollection();
        $this->acquiredLevel = null;
        $this->acquiredSkillAt = new DateTime();
    }

    public function setSkill(Skill $skill): self
    {
        $this->skill = $skill;

        return $this;
    }

    /**
     * Get skill.
     *
     * @return Skill
     */
    public function getSkill()
    {
        return $this->skill;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course.
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    public function setSession(Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session.
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    public function setAcquiredSkillAt(DateTime $acquiredSkillAt): self
    {
        $this->acquiredSkillAt = $acquiredSkillAt;

        return $this;
    }

    /**
     * Get acquiredSkillAt.
     *
     * @return DateTime
     */
    public function getAcquiredSkillAt()
    {
        return $this->acquiredSkillAt;
    }

    public function setAssignedBy(int $assignedBy): self
    {
        $this->assignedBy = $assignedBy;

        return $this;
    }

    /**
     * Get assignedBy.
     *
     * @return int
     */
    public function getAssignedBy()
    {
        return $this->assignedBy;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setAcquiredLevel(Level $acquiredLevel): self
    {
        $this->acquiredLevel = $acquiredLevel;

        return $this;
    }

    /**
     * Get acquiredLevel.
     *
     * @return Level
     */
    public function getAcquiredLevel()
    {
        return $this->acquiredLevel;
    }

    public function setArgumentationAuthorId(int $argumentationAuthorId): self
    {
        $this->argumentationAuthorId = $argumentationAuthorId;

        return $this;
    }

    /**
     * Get argumentationAuthorId.
     *
     * @return int
     */
    public function getArgumentationAuthorId()
    {
        return $this->argumentationAuthorId;
    }

    public function setArgumentation(string $argumentation): self
    {
        $this->argumentation = $argumentation;

        return $this;
    }

    /**
     * Get argumentation.
     *
     * @return string
     */
    public function getArgumentation()
    {
        return $this->argumentation;
    }

    /**
     * Get the source which the skill was obtained.
     *
     * @return string
     */
    public function getSourceName()
    {
        $source = '';
        if (null !== $this->session) {
            $source .= sprintf('[%s] ', $this->session->getName());
        }

        if (null !== $this->course) {
            $source .= $this->course->getTitle();
        }

        return $source;
    }

    /**
     * Get the URL for the issue.
     *
     * @return string
     */
    public function getIssueUrl()
    {
        return api_get_path(WEB_PATH).sprintf('badge/%s', $this->id);
    }

    /**
     * Get the URL for the All issues page.
     *
     * @return string
     */
    public function getIssueUrlAll()
    {
        return api_get_path(WEB_PATH).sprintf('skill/%s/user/%s', $this->skill->getId(), $this->user->getId());
    }

    /**
     * Get the URL for the assertion.
     *
     * @return string
     */
    public function getAssertionUrl()
    {
        $url = api_get_path(WEB_CODE_PATH).'badge/assertion.php?';

        return $url.http_build_query([
            'user' => $this->user->getId(),
            'skill' => $this->skill->getId(),
            'course' => null !== $this->course ? $this->course->getId() : 0,
            'session' => null !== $this->session ? $this->session->getId() : 0,
        ]);
    }

    public function getComments(bool $sortDescByDateTime = false): Collection
    {
        if ($sortDescByDateTime) {
            $criteria = Criteria::create();
            $criteria->orderBy([
                'feedbackDateTime' => Criteria::DESC,
            ]);

            return $this->comments->matching($criteria);
        }

        return $this->comments;
    }

    /**
     * Calculate the average value from the feedback comments.
     */
    public function getAverage(): string
    {
        $sum = 0;
        $countValues = 0;
        foreach ($this->comments as $comment) {
            if (0 === $comment->getFeedbackValue()) {
                continue;
            }

            $sum += $comment->getFeedbackValue();
            $countValues++;
        }

        $average = $countValues > 0 ? $sum / $countValues : 0;

        return number_format($average, 2);
    }

    /**
     * @return int
     */
    public function getValidationStatus()
    {
        return $this->validationStatus;
    }

    /**
     * @return SkillRelUser
     */
    public function setValidationStatus(int $validationStatus)
    {
        $this->validationStatus = $validationStatus;

        return $this;
    }
}
