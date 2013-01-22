<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGradebookResult
 *
 * @Table(name="gradebook_result")
 * @Entity
 */
class EntityGradebookResult
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="evaluation_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $evaluationId;

    /**
     * @var \DateTime
     *
     * @Column(name="created_at", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $createdAt;

    /**
     * @var float
     *
     * @Column(name="score", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $score;


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
     * Set userId
     *
     * @param integer $userId
     * @return EntityGradebookResult
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
     * @return EntityGradebookResult
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
     * @return EntityGradebookResult
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
     * @return EntityGradebookResult
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
}
