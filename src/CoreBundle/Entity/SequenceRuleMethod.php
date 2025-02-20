<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'sequence_rule_method')]
#[ORM\Entity]
class SequenceRuleMethod
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'method_order', type: 'integer')]
    protected int $methodOrder;

    #[ORM\ManyToOne(targetEntity: SequenceRule::class)]
    #[ORM\JoinColumn(name: 'sequence_rule_id', referencedColumnName: 'id')]
    protected ?SequenceRule $rule = null;

    #[ORM\ManyToOne(targetEntity: SequenceMethod::class)]
    #[ORM\JoinColumn(name: 'sequence_method_id', referencedColumnName: 'id')]
    protected ?SequenceMethod $method = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMethodOrder(): int
    {
        return $this->methodOrder;
    }

    public function setMethodOrder(int $methodOrder): static
    {
        $this->methodOrder = $methodOrder;

        return $this;
    }

    public function getRule(): ?SequenceRule
    {
        return $this->rule;
    }

    public function setRule(SequenceRule $rule): static
    {
        $this->rule = $rule;

        return $this;
    }

    public function getMethod(): SequenceMethod
    {
        return $this->method;
    }

    public function setMethod(SequenceMethod $method): static
    {
        $this->method = $method;

        return $this;
    }
}
