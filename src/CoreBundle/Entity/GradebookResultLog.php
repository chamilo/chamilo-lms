<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * GradebookResultLog.
 *
 * @ORM\Table(name="gradebook_result_log")
 * @ORM\Entity
 */
class GradebookResultLog
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookResult")
     * @ORM\JoinColumn(name="result_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookResult $result;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookEvaluation")
     * @ORM\JoinColumn(name="evaluation_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookEvaluation $evaluation;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected DateTime $createdAt;

    /**
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=true)
     */
    protected ?float $score = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookResultLogs")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * Set createdAt.
     *
     * @return GradebookResultLog
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set score.
     *
     * @return GradebookResultLog
     */
    public function setScore(float $score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score.
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
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
}
