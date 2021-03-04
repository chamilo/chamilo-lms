<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * GradebookResult.
 *
 * @ORM\Table(name="gradebook_result",
 *     indexes={
 *         @ORM\Index(name="idx_gb_uid_eid", columns={"user_id", "evaluation_id"}),
 *     })
 *
 *     @ORM\Entity
 */
class GradebookResult
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookEvaluation")
     * @ORM\JoinColumn(name="evaluation_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookEvaluation $evaluation;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookResults")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=true)
     */
    protected ?float $score;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected DateTime $createdAt;

    /**
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return GradebookResult
     */
    public function setCreatedAt($createdAt)
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
     * @param float $score
     *
     * @return GradebookResult
     */
    public function setScore($score)
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

    public function getEvaluation(): GradebookEvaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(GradebookEvaluation $evaluation): self
    {
        $this->evaluation = $evaluation;

        return $this;
    }
}
