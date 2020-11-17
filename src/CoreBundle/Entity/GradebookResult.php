<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * GradebookResult.
 *
 * @ORM\Table(name="gradebook_result",
 *  indexes={
 *     @ORM\Index(name="idx_gb_uid_eid", columns={"user_id", "evaluation_id"}),
 * })
 *
 * @ORM\Entity
 */
class GradebookResult
{
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="evaluation_id", type="integer", nullable=false)
     */
    protected $evaluationId;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=true)
     */
    protected $score;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookResults")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * Set evaluationId.
     *
     * @param int $evaluationId
     *
     * @return GradebookResult
     */
    public function setEvaluationId($evaluationId)
    {
        $this->evaluationId = $evaluationId;

        return $this;
    }

    /**
     * Get evaluationId.
     *
     * @return int
     */
    public function getEvaluationId()
    {
        return $this->evaluationId;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
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
     * @return \DateTime
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
}
