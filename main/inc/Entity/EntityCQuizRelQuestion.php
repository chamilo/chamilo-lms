<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCQuizRelQuestion
 *
 * @Table(name="c_quiz_rel_question")
 * @Entity
 */
class EntityCQuizRelQuestion
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $questionId;

    /**
     * @var integer
     *
     * @Column(name="exercice_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $exerciceId;

    /**
     * @var integer
     *
     * @Column(name="question_order", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionOrder;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCQuizRelQuestion
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
     * Set questionId
     *
     * @param integer $questionId
     * @return EntityCQuizRelQuestion
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;

        return $this;
    }

    /**
     * Get questionId
     *
     * @return integer 
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * Set exerciceId
     *
     * @param integer $exerciceId
     * @return EntityCQuizRelQuestion
     */
    public function setExerciceId($exerciceId)
    {
        $this->exerciceId = $exerciceId;

        return $this;
    }

    /**
     * Get exerciceId
     *
     * @return integer 
     */
    public function getExerciceId()
    {
        return $this->exerciceId;
    }

    /**
     * Set questionOrder
     *
     * @param integer $questionOrder
     * @return EntityCQuizRelQuestion
     */
    public function setQuestionOrder($questionOrder)
    {
        $this->questionOrder = $questionOrder;

        return $this;
    }

    /**
     * Get questionOrder
     *
     * @return integer 
     */
    public function getQuestionOrder()
    {
        return $this->questionOrder;
    }
}
