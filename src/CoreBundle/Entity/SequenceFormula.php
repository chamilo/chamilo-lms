<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceFormula.
 *
 * @ORM\Table(name="sequence_formula")
 * @ORM\Entity
 */
class SequenceFormula
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceMethod")
     * @ORM\JoinColumn(name="sequence_method_id", referencedColumnName="id")
     */
    protected ?\Chamilo\CoreBundle\Entity\SequenceMethod $method = null;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceVariable")
     * @ORM\JoinColumn(name="sequence_variable_id", referencedColumnName="id")
     */
    protected ?\Chamilo\CoreBundle\Entity\SequenceVariable $variable = null;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return SequenceFormula
     */
    public function setMethod(?SequenceMethod $method)
    {
        $this->method = $method;

        return $this;
    }

    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * @return SequenceFormula
     */
    public function setVariable(?SequenceVariable $variable)
    {
        $this->variable = $variable;

        return $this;
    }
}
