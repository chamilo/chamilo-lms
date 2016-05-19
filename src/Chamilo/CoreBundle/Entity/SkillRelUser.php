<?php

/* For licensing terms, see /license.txt */

/**
 * SkillRelUser Entity
 *
 * @package chamilo.skill
 */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="skill_id", type="integer", nullable=false)
     */
    private $skillId;

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
     * @var integer
     *
     * @ORM\Column(name="course_id", type="integer", nullable=false)
     */
    private $courseId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id")
     */
    private $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="issuedSkills", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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

    public function __construct()
    {
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return SkillRelUser
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set skillId
     *
     * @param integer $skillId
     * @return SkillRelUser
     */
    public function setSkillId($skillId)
    {
        $this->skillId = $skillId;

        return $this;
    }

    /**
     * Get skillId
     *
     * @return integer
     */
    public function getSkillId()
    {
        return $this->skillId;
    }

    /**
     * Set user
     * @param \Chamilo\UserBundle\Entity\User $user
     * @return \Chamilo\CoreBundle\Entity\SkillRelUser
     */
    public function setUser(\Chamilo\UserBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     * @return \Chamilo\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set skill
     * @param \Chamilo\CoreBundle\Entity\Skill $skill
     * @return \Chamilo\CoreBundle\Entity\SkillRelUser
     */
    public function setSkill(Skill $skill)
    {
        $this->skill = $skill;

        return $this;
    }

    /**
     * Get skill
     * @return \Chamilo\CoreBundle\Entity\Skill
     */
    public function getSkill()
    {
        return $this->skill;
    }

    /**
     * Set course
     * @param \Chamilo\CoreBundle\Entity\Course $course
     * @return \Chamilo\CoreBundle\Entity\SkillRelUser
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course
     * @return \Chamilo\CoreBundle\Entity\Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set session
     * @param \Chamilo\CoreBundle\Entity\Session $session
     * @return \Chamilo\CoreBundle\Entity\SkillRelUser
     */
    public function setSession(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     * @return \Chamilo\CoreBundle\Entity\Session
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
     * Set courseId
     *
     * @param integer $courseId
     * @return SkillRelUser
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * Get courseId
     *
     * @return integer
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return SkillRelUser
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
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
     * @param \Chamilo\SkillBundle\Entity\Level $acquiredLevel
     * @return \Chamilo\CoreBundle\Entity\SkillRelUser
     */
    public function setAcquiredLevel($acquiredLevel)
    {
        $this->acquiredLevel = $acquiredLevel;

        return $this;
    }

    /**
     * Get acquiredLevel
     * @return \Chamilo\SkillBundle\Entity\Level
     */
    public function getAcquiredLevel()
    {
        return $this->acquiredLevel;
    }

    /**
     * Set argumentationAuthorId
     * @param integer $argumentationAuthorId
     * @return \Chamilo\CoreBundle\Entity\SkillRelUser
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
     * @return \Chamilo\CoreBundle\Entity\SkillRelUser
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
        return api_get_path(WEB_PATH) . "badge/{$this->id}";
    }

    /**
     * Get the URL for the All issues page
     * @return string
     */
    public function getIssueUrlAll()
    {
        return api_get_path(WEB_PATH) . "skill/{$this->skill->getId()}/user/{$this->user->getId()}";
    }

    /**
     * Get the URL for the assertion
     * @return string
     */
    public function getAssertionUrl()
    {
        $url = api_get_path(WEB_CODE_PATH) . "badge/assertion.php?";

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
            $criteria = \Doctrine\Common\Collections\Criteria::create();
            $criteria->orderBy([
                'feedbackDateTime' => \Doctrine\Common\Collections\Criteria::DESC
            ]);

            return $this->comments->matching($criteria);
        }

        return $this->comments;
    }

    /**
     * Calculate the average value from the feedback comments
     * @return type
     */
    public function getAverage()
    {
        $sum = 0;
        $average = 0;
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
