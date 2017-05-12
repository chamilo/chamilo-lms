<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\SkillBundle\Entity\Level;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * SkillRelUser
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
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="achievedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;
    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Skill", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="skill_id", referencedColumnName="id", nullable=false)
     */
    private $skill;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="acquired_skill_at", type="datetime", nullable=false)
     */
    private $acquiredSkillAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="assigned_by", type="integer", nullable=false)
     */
    private $assignedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id", nullable=true)
     */
    private $course;

    /**
     * @var Session
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     */
    private $session;

    /**
     * @var Level
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\SkillBundle\Entity\Level")
     * @ORM\JoinColumn(name="acquired_level", referencedColumnName="id")
     */
    private $acquiredLevel;

    /**
     * @var string
     *
     * @ORM\Column(name="argumentation", type="text")
     */
    private $argumentation;

    /**
     * @var integer
     *
     * @ORM\Column(name="argumentation_author_id", type="integer")
     */
    private $argumentationAuthorId;

    /**
     * @ORM\OneToMany(targetEntity="SkillRelUserComment", mappedBy="skillRelUser")
     */
    protected $comments;

    /**
     * SkillRelUser constructor.
     */
    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    /**
     * Set user
     * @param User $user
     * @return SkillRelUser
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set skill
     * @param Skill $skill
     * @return SkillRelUser
     */
    public function setSkill(Skill $skill)
    {
        $this->skill = $skill;

        return $this;
    }

    /**
     * Get skill
     * @return Skill
     */
    public function getSkill()
    {
        return $this->skill;
    }

    /**
     * Set course
     * @param Course $course
     * @return SkillRelUser
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set session
     * @param Session $session
     * @return SkillRelUser
     */
    public function setSession(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }


    /**
     * Set acquiredSkillAt
     *
     * @param \DateTime $acquiredSkillAt
     * @return SkillRelUser
     */
    public function setAcquiredSkillAt($acquiredSkillAt)
    {
        $this->acquiredSkillAt = $acquiredSkillAt;

        return $this;
    }

    /**
     * Get acquiredSkillAt
     *
     * @return \DateTime
     */
    public function getAcquiredSkillAt()
    {
        return $this->acquiredSkillAt;
    }

    /**
     * Set assignedBy
     *
     * @param integer $assignedBy
     * @return SkillRelUser
     */
    public function setAssignedBy($assignedBy)
    {
        $this->assignedBy = $assignedBy;

        return $this;
    }

    /**
     * Get assignedBy
     *
     * @return integer
     */
    public function getAssignedBy()
    {
        return $this->assignedBy;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set acquiredLevel
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
     * Get acquiredLevel
     * @return Level
     */
    public function getAcquiredLevel()
    {
        return $this->acquiredLevel;
    }

    /**
     * Set argumentationAuthorId
     * @param int $argumentationAuthorId
     * @return SkillRelUser
     */
    public function setArgumentationAuthorId($argumentationAuthorId)
    {
        $this->argumentationAuthorId = $argumentationAuthorId;

        return $this;
    }

    /**
     * Get argumentationAuthorId
     * @return integer
     */
    public function getArgumentationAuthorId()
    {
        return $this->argumentationAuthorId;
    }

    /**
     * Set argumentation
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
     * Get argumentation
     * @return string
     */
    public function getArgumentation()
    {
        return $this->argumentation;
    }

    /**
     * Get the source which the skill was obtained
     * @return string
     */
    public function getSourceName()
    {
        $source = '';

        if ($this->session && $this->session->getId() != 0) {

            $source .= "[{$this->session->getName()}] ";
        }

        if ($this->course) {
            $source .= $this->course->getTitle();
        }

        return $source;
    }

    /**
     * Get the URL for the issue
     * @return string
     */
    public function getIssueUrl()
    {
        return api_get_path(WEB_PATH)."badge/{$this->id}";
    }

    /**
     * Get the URL for the All issues page
     * @return string
     */
    public function getIssueUrlAll()
    {
        return api_get_path(WEB_PATH)."skill/{$this->skill->getId()}/user/{$this->user->getId()}";
    }

    /**
     * Get the URL for the assertion
     * @return string
     */
    public function getAssertionUrl()
    {
        $url = api_get_path(WEB_CODE_PATH)."badge/assertion.php?";

        $url .= http_build_query(array(
            'user' => $this->user->getId(),
            'skill' => $this->skill->getId(),
            'course' => $this->course ? $this->course->getId() : 0,
            'session' => $this->session ? $this->session->getId() : 0
        ));

        return $url;
    }

    /**
     * Get comments
     * @param boolean $sortDescByDateTime
     * @return ArrayCollection
     */
    public function getComments($sortDescByDateTime = false)
    {
        if ($sortDescByDateTime) {
            $criteria = Criteria::create();
            $criteria->orderBy([
                'feedbackDateTime' => Criteria::DESC
            ]);

            return $this->comments->matching($criteria);
        }

        return $this->comments;
    }

    /**
     * Calculate the average value from the feedback comments
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
