<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SkillRelUser
 *
 * @ORM\Table(name="skill_rel_user", indexes={@ORM\Index(name="idx_select_cs", columns={"course_id", "session_id"})})
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
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



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
}
