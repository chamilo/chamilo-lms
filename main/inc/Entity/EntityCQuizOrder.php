<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCQuizOrder
 *
 * @Table(name="c_quiz_order")
 * @Entity
 */
class EntityCQuizOrder
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
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @Column(name="exercise_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exerciseId;

    /**
     * @var integer
     *
     * @Column(name="exercise_order", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exerciseOrder;


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
     * Set cId
     *
     * @param integer $cId
     * @return EntityCQuizOrder
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCQuizOrder
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
     * Set exerciseId
     *
     * @param integer $exerciseId
     * @return EntityCQuizOrder
     */
    public function setExerciseId($exerciseId)
    {
        $this->exerciseId = $exerciseId;

        return $this;
    }

    /**
     * Get exerciseId
     *
     * @return integer 
     */
    public function getExerciseId()
    {
        return $this->exerciseId;
    }

    /**
     * Set exerciseOrder
     *
     * @param integer $exerciseOrder
     * @return EntityCQuizOrder
     */
    public function setExerciseOrder($exerciseOrder)
    {
        $this->exerciseOrder = $exerciseOrder;

        return $this;
    }

    /**
     * Get exerciseOrder
     *
     * @return integer 
     */
    public function getExerciseOrder()
    {
        return $this->exerciseOrder;
    }
}
