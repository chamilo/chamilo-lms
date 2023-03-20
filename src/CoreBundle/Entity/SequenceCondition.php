<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceCondition.
 *
 * @ORM\Table(name="sequence_condition")
 * @ORM\Entity
 */
class SequenceCondition
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected string $description;

    /**
     * @ORM\Column(name="mat_op", type="string")
     */
    protected string $mathOperation;

    /**
     * @ORM\Column(name="param", type="float")
     */
    protected string $param;

    /**
     * @ORM\Column(name="act_true", type="integer")
     */
    protected string $actTrue;

    /**
     * @ORM\Column(name="act_false", type="string")
     */
    protected string $actFalse;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return SequenceCondition
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getMathOperation()
    {
        return $this->mathOperation;
    }

    public function setMathOperation(string $mathOperation): self
    {
        $this->mathOperation = $mathOperation;

        return $this;
    }

    /**
     * @return string
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * @return SequenceCondition
     */
    public function setParam(string $param): self
    {
        $this->param = $param;

        return $this;
    }

    /**
     * @return string
     */
    public function getActTrue()
    {
        return $this->actTrue;
    }

    /**
     * @return SequenceCondition
     */
    public function setActTrue(string $actTrue): self
    {
        $this->actTrue = $actTrue;

        return $this;
    }

    /**
     * @return string
     */
    public function getActFalse()
    {
        return $this->actFalse;
    }

    /**
     * @return SequenceCondition
     */
    public function setActFalse(string $actFalse): self
    {
        $this->actFalse = $actFalse;

        return $this;
    }
}
