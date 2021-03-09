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
     * @ORM\ManyToOne(targetEntity="SequenceRule")
     * @ORM\JoinColumn(name="sequence_rule_id", referencedColumnName="id")
     */
    protected ?SequenceRule $rule = null;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceMethod")
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

    public function setMethodOrder(string $methodOrder): void
    {
        $this->methodOrder = $methodOrder;
    }

    public function getRule()
    {
        return $this->rule;
    }

    public function setRule(SequenceRule $rule): void
    {
        $this->rule = $rule;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod(SequenceMethod $method): void
    {
        $this->method = $method;
    }
}
