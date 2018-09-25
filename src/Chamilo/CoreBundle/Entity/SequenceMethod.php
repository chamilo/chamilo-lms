<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceMethod.
 *
 * @ORM\Table(name="sequence_method")
 * @ORM\Entity
 */
class SequenceMethod
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
     * @ORM\Column(name="formula", type="text")
     */
    protected $formula;

    /**
     * @var string
     *
     * @ORM\Column(name="assign", type="integer")
     */
    protected $assign;

    /**
     * @var string
     *
     * @ORM\Column(name="met_type", type="string")
     */
    protected $metType;

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
     * @return SequenceMethod
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormula()
    {
        return $this->formula;
    }

    /**
     * @param string $formula
     *
     * @return SequenceMethod
     */
    public function setFormula($formula)
    {
        $this->formula = $formula;

        return $this;
    }

    /**
     * @return string
     */
    public function getAssign()
    {
        return $this->assign;
    }

    /**
     * @param string $assign
     *
     * @return SequenceMethod
     */
    public function setAssign($assign)
    {
        $this->assign = $assign;

        return $this;
    }

    /**
     * @return string
     */
    public function getMetType()
    {
        return $this->metType;
    }

    /**
     * @param string $metType
     *
     * @return SequenceMethod
     */
    public function setMetType($metType)
    {
        $this->metType = $metType;

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
     * @return SequenceMethod
     */
    public function setActFalse($actFalse)
    {
        $this->actFalse = $actFalse;

        return $this;
    }
}
