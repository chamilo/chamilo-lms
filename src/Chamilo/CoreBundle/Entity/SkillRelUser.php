<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\SkillBundle\Entity\Level;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * SkillRelUser.
 *
 * @ORM\Table(
 *  name="skill_rel_user",
 *  indexes={
 *      @ORM\Index(name="idx_select_cs", columns={"course_id", "session_id"}),
 *      @ORM\Index(name="idx_select_s_c_u", columns={"session_id", "course_id", "user_id"}),
 *      @ORM\Index(name="idx_select_sk_u", columns={"skill_id", "user_id"})
 *  }
 * )
 * @ORM\Entity
 */
class SkillRelUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="SkillRelUserComment", mappedBy="skillRelUser")
     */
    protected $comments;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="achievedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Skill", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="skill_id", referencedColumnName="id", nullable=false)
     */
    protected $skill;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="acquired_skill_at", type="datetime", nullable=false)
     */
    protected $acquiredSkillAt;

    /**
     * @var int
     *
     * @ORM\Column(name="assigned_by", type="integer", nullable=false)
     */
    protected $assignedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id", nullable=true)
     */
    protected $course;

    /**
     * @var Session
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     */
    protected $session;

    /**
     * @var Level
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\SkillBundle\Entity\Level")
     * @ORM\JoinColumn(name="acquired_level", referencedColumnName="id")
     */
    protected $acquiredLevel;

    /**
     * @var string
     *
     * @ORM\Column(name="argumentation", type="text")
     */
    protected $argumentation;

    /**
     * @var int
     *
     * @ORM\Column(name="argumentation_author_id", type="integer")
     */
    protected $argumentationAuthorId;

    /**
     * SkillRelUser constructor.
     */
    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    /**
     * Set user.
     *
     * @return SkillRelUser
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set skill.
     *
     * @return SkillRelUser
     */
    public function setSkill(Skill $skill)
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

    /**
     * Set course.
     *
     * @return SkillRelUser
     */
    public function setCourse(Course $course)
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

    /**
     * Set session.
     *
     * @return SkillRelUser
     */
    public function setSession(Session $session)
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

    /**
     * Set acquiredSkillAt.
     *
     * @param \DateTime $acquiredSkillAt
     *
     * @return SkillRelUser
     */
    public function setAcquiredSkillAt($acquiredSkillAt)
    {
        $this->acquiredSkillAt = $acquiredSkillAt;

        return $this;
    }

    /**
     * Get acquiredSkillAt.
     *
     * @return \DateTime
     */
    public function getAcquiredSkillAt()
    {
        return $this->acquiredSkillAt;
    }

    /**
     * Set assignedBy.
     *
     * @param int $assignedBy
     *
     * @return SkillRelUser
     */
    public function setAssignedBy($assignedBy)
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

    /**
     * Set acquiredLevel.
     *
     * @param Level $acquiredLevel
     *
     * @return SkillRelUser
     */
    public function setAcquiredLevel($acquiredLevel)
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

    /**
     * Set argumentationAuthorId.
     *
     * @param int $argumentationAuthorId
     *
     * @return SkillRelUser
     */
    public function setArgumentationAuthorId($argumentationAuthorId)
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

    /**
     * Set argumentation.
     *
     * @param string $argumentation
     *
     * @return SkillRelUser
     */
    public function setArgumentation($argumentation)
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

        if ($this->session && $this->getSession()->getId() != 0) {
            $source .= "[{$this->getSession()->getName()}] ";
        }

        if ($this->course) {
            $source .= $this->getCourse()->getTitle();
        }

        return $source;
    }

    /**
     * Get comments.
     *
     * @param bool $sortDescByDateTime
     *
     * @return ArrayCollection
     */
    public function getComments($sortDescByDateTime = false)
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
     *
     * @return string
     */
    public function getAverage()
    {
        $sum = 0;
        $countValues = 0;

        foreach ($this->comments as $comment) {
            if (!$comment->getFeedbackValue()) {
                continue;
            }

            $sum += $comment->getFeedbackValue();
            $countValues++;
        }

        $average = $countValues > 0 ? $sum / $countValues : 0;

        return number_format($average, 2);
    }
}
