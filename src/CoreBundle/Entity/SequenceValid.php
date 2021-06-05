<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="sequence_valid")
 * @ORM\Entity
 */
class SequenceValid
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceVariable")
     * @ORM\JoinColumn(name="sequence_variable_id", referencedColumnName="id")
     */
    protected ?SequenceVariable $variable = null;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceCondition")
     * @ORM\JoinColumn(name="sequence_condition_id", referencedColumnName="id")
     */
    protected ?SequenceCondition $condition = null;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * @return SequenceValid
     */
    public function setVariable(?SequenceVariable $variable): self
    {
        $this->variable = $variable;

        return $this;
    }

    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return SequenceValid
     */
    public function setCondition(?SequenceCondition $condition): self
    {
        $this->condition = $condition;

        return $this;
    }
}
