<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected string $description;

    /**
     * @ORM\Column(name="formula", type="text")
     */
    protected string $formula;

    /**
     * @ORM\Column(name="assign", type="integer")
     */
    protected string $assign;

    /**
     * @ORM\Column(name="met_type", type="string")
     */
    protected string $metType;

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
     * @return SequenceMethod
     */
    public function setDescription(string $description): self
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
     * @return SequenceMethod
     */
    public function setFormula(string $formula): self
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
     * @return SequenceMethod
     */
    public function setAssign(string $assign): self
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
     * @return SequenceMethod
     */
    public function setMetType(string $metType): self
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
     * @return SequenceMethod
     */
    public function setActFalse(string $actFalse): self
    {
        $this->actFalse = $actFalse;

        return $this;
    }
}
