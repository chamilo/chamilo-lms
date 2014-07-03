<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsergroupRelQuestion
 *
 * @ORM\Table(name="usergroup_rel_question")
 * @ORM\Entity
 */
class UsergroupRelQuestion
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="usergroup_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $usergroupId;

    /**
     * @var float
     *
     * @ORM\Column(name="coefficient", type="float", precision=10, scale=0, nullable=true, unique=false)
     */
    private $coefficient;


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
     * @return UsergroupRelQuestion
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
     * @return UsergroupRelQuestion
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
     * Set usergroupId
     *
     * @param integer $usergroupId
     * @return UsergroupRelQuestion
     */
    public function setUsergroupId($usergroupId)
    {
        $this->usergroupId = $usergroupId;

        return $this;
    }

    /**
     * Get usergroupId
     *
     * @return integer
     */
    public function getUsergroupId()
    {
        return $this->usergroupId;
    }

    /**
     * Set coefficient
     *
     * @param float $coefficient
     * @return UsergroupRelQuestion
     */
    public function setCoefficient($coefficient)
    {
        $this->coefficient = $coefficient;

        return $this;
    }

    /**
     * Get coefficient
     *
     * @return float
     */
    public function getCoefficient()
    {
        return $this->coefficient;
    }
}
