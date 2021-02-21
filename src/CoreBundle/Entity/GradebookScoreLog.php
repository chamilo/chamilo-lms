<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookScoreLog.
 *
 * @ORM\Table(
 *      name="gradebook_score_log", indexes={
 *          @ORM\Index(name="idx_gradebook_score_log_user", columns={"user_id"}),
 *          @ORM\Index(name="idx_gradebook_score_log_user_category", columns={"user_id", "category_id"})
 *      }
 * )
 * @ORM\Entity
 */
class GradebookScoreLog
{
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory")
     * @ORM\JoinColumn(name="category_id",referencedColumnName="id")
     */
    protected GradebookCategory $category;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookScoreLogs")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=false)
     */
    protected $score;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registered_at", type="datetime", nullable=false)
     */
    protected $registeredAt;

    /**
     * Get the category id.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Get the achieved score.
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Get the datetime of register.
     *
     * @return \DateTime
     */
    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }

    /**
     * Get the id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the category id.
     *
     * @param int $categoryId
     *
     * @return $this
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Set the achieved score.
     *
     * @param float $score
     *
     * @return $this
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Set the datetime of register.
     *
     * @return $this
     */
    public function setRegisteredAt(\DateTime $registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }
}
