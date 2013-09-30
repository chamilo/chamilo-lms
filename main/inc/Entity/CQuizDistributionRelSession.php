<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * CQuizDistributionRelSession
 *
 * @ORM\Table(name="c_quiz_distribution_rel_session")
 * @ORM\Entity
 */
class CQuizDistributionRelSession
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exerciseId;

    /**
     * @var integer
     *
     * @ORM\Column(name="quiz_distribution_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $quizDistributionId;

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
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CQuizDistributionRelSession
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
     * Set cId
     *
     * @param integer $cId
     * @return CQuizDistributionRelSession
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
     * Set exerciseId
     *
     * @param integer $exerciseId
     * @return CQuizDistributionRelSession
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
     * Set quizDistributionId
     *
     * @param integer $quizDistributionId
     * @return CQuizDistributionRelSession
     */
    public function setQuizDistributionId($quizDistributionId)
    {
        $this->quizDistributionId = $quizDistributionId;

        return $this;
    }

    /**
     * Get quizDistributionId
     *
     * @return integer
     */
    public function getQuizDistributionId()
    {
        return $this->quizDistributionId;
    }
}
