<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsergroupRelQuestion.
 *
 * @ORM\Table(name="usergroup_rel_question")
 * @ORM\Entity
 */
class UsergroupRelQuestion
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    protected $questionId;

    /**
     * @var int
     *
     * @ORM\Column(name="usergroup_id", type="integer", nullable=false)
     */
    protected $usergroupId;

    /**
     * @var float
     *
     * @ORM\Column(name="coefficient", type="float", precision=6, scale=2, nullable=true)
     */
    protected $coefficient;

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return UsergroupRelQuestion
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set questionId.
     *
     * @param int $questionId
     *
     * @return UsergroupRelQuestion
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;

        return $this;
    }

    /**
     * Get questionId.
     *
     * @return int
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * Set usergroupId.
     *
     * @param int $usergroupId
     *
     * @return UsergroupRelQuestion
     */
    public function setUsergroupId($usergroupId)
    {
        $this->usergroupId = $usergroupId;

        return $this;
    }

    /**
     * Get usergroupId.
     *
     * @return int
     */
    public function getUsergroupId()
    {
        return $this->usergroupId;
    }

    /**
     * Set coefficient.
     *
     * @param float $coefficient
     *
     * @return UsergroupRelQuestion
     */
    public function setCoefficient($coefficient)
    {
        $this->coefficient = $coefficient;

        return $this;
    }

    /**
     * Get coefficient.
     *
     * @return float
     */
    public function getCoefficient()
    {
        return $this->coefficient;
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
