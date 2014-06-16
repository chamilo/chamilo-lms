<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizOrder
 *
 * @ORM\Table(name="c_quiz_order")
 * @ORM\Entity
 */
class CQuizOrder
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exerciseId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_order", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exerciseOrder;


    /**
     * Get iid
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CQuizOrder
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
     * @return CQuizOrder
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
     * @return CQuizOrder
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
     * @return CQuizOrder
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
