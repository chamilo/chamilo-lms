<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected int $cId;

    /**
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    protected int $questionId;

    /**
     * @ORM\Column(name="usergroup_id", type="integer", nullable=false)
     */
    protected int $usergroupId;

    /**
     * @ORM\Column(name="coefficient", type="float", precision=6, scale=2, nullable=true)
     */
    protected ?float $coefficient = null;

    /**
     * Set cId.
     *
     * @return UsergroupRelQuestion
     */
    public function setCId(int $cId)
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
     * @return UsergroupRelQuestion
     */
    public function setQuestionId(int $questionId)
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
     * @return UsergroupRelQuestion
     */
    public function setUsergroupId(int $usergroupId)
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
     * @return UsergroupRelQuestion
     */
    public function setCoefficient(float $coefficient)
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
