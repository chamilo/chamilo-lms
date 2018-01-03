<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizRelQuestion
 *
 * @ORM\Table(
 *  name="c_quiz_rel_question",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="question", columns={"question_id"}),
 *      @ORM\Index(name="exercise", columns={"exercice_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CQuizRelQuestion
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_order", type="integer", nullable=false)
     */
    private $questionOrder;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer")
     */
    private $questionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercice_id", type="integer")
     */
    private $exerciceId;

    /**
     * Set questionOrder
     *
     * @param integer $questionOrder
     * @return CQuizRelQuestion
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

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CQuizRelQuestion
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
     * @return CQuizRelQuestion
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
     * @return CQuizRelQuestion
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
}
