<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookResultLog
 *
 * @ORM\Table(name="gradebook_result_log")
 * @ORM\Entity
 */
class GradebookResultLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_result", type="integer", nullable=false)
     */
    private $idResult;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="evaluation_id", type="integer", nullable=false)
     */
    private $evaluationId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=true)
     */
    private $score;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set idResult
     *
     * @param integer $idResult
     * @return GradebookResultLog
     */
    public function setIdResult($idResult)
    {
        $this->idResult = $idResult;

        return $this;
    }

    /**
     * Get idResult
     *
     * @return integer
     */
    public function getIdResult()
    {
        return $this->idResult;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return GradebookResultLog
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
     * Set evaluationId
     *
     * @param integer $evaluationId
     * @return GradebookResultLog
     */
    public function setEvaluationId($evaluationId)
    {
        $this->evaluationId = $evaluationId;

        return $this;
    }

    /**
     * Get evaluationId
     *
     * @return integer
     */
    public function getEvaluationId()
    {
        return $this->evaluationId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return GradebookResultLog
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set score
     *
     * @param float $score
     * @return GradebookResultLog
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
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
