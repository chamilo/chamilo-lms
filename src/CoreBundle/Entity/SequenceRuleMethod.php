<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceRuleMethod.
 *
 * @ORM\Table(name="sequence_rule_method")
 * @ORM\Entity
 */
class SequenceRuleMethod
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\Column(name="method_order", type="integer")
     */
    protected string $methodOrder;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SequenceRule")
     * @ORM\JoinColumn(name="sequence_rule_id", referencedColumnName="id")
     */
    protected ?SequenceRule $rule = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SequenceMethod")
     * @ORM\JoinColumn(name="sequence_method_id", referencedColumnName="id")
     */
    protected ?SequenceMethod $method = null;

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
    public function getMethodOrder()
    {
        return $this->methodOrder;
    }

    public function setMethodOrder(string $methodOrder): self
    {
        $this->methodOrder = $methodOrder;

        return $this;
    }

    public function getRule(): ?SequenceRule
    {
        return $this->rule;
    }

    public function setRule(SequenceRule $rule): self
    {
        $this->rule = $rule;

        return $this;
    }

    public function getMethod(): SequenceMethod
    {
        return $this->method;
    }

    public function setMethod(SequenceMethod $method): self
    {
        $this->method = $method;

        return $this;
    }
}
