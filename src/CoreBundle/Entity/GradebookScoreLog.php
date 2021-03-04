<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookScoreLog.
 *
 * @ORM\Table(
 *     name="gradebook_score_log", indexes={
 *         @ORM\Index(name="idx_gradebook_score_log_user", columns={"user_id"}),
 *         @ORM\Index(name="idx_gradebook_score_log_user_category", columns={"user_id", "category_id"})
 *     }
 * )
 * @ORM\Entity
 */
class GradebookScoreLog
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookCategory $category;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookScoreLogs")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $score;

    /**
     * @ORM\Column(name="registered_at", type="datetime", nullable=false)
     */
    protected DateTime $registeredAt;

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
     * @return DateTime
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
    public function setRegisteredAt(DateTime $registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }
}
