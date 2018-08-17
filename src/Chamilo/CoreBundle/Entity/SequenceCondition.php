<?php
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
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="mat_op", type="string")
     */
    protected $mathOperation;

    /**
     * @var string
     *
     * @ORM\Column(name="param", type="float")
     */
    protected $param;

    /**
     * @var string
     *
     * @ORM\Column(name="act_true", type="integer")
     */
    protected $actTrue;

    /**
     * @var string
     *
     * @ORM\Column(name="act_false", type="string")
     */
    protected $actFalse;

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
     * @param string $description
     *
     * @return SequenceCondition
     */
    public function setDescription($description)
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

    /**
     * @param string $mathOperation
     *
     * @return SequenceCondition
     */
    public function setMathOperation($mathOperation)
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
     * @param string $param
     *
     * @return SequenceCondition
     */
    public function setParam($param)
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
     * @param string $actTrue
     *
     * @return SequenceCondition
     */
    public function setActTrue($actTrue)
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
     * @param string $actFalse
     *
     * @return SequenceCondition
     */
    public function setActFalse($actFalse)
    {
        $this->actFalse = $actFalse;

        return $this;
    }
}
