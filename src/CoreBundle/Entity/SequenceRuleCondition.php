<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceRuleCondition.
 *
 * @ORM\Table(name="sequence_rule_condition")
 * @ORM\Entity
 */
class SequenceRuleCondition
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SequenceRule")
     * @ORM\JoinColumn(name="sequence_rule_id", referencedColumnName="id")
     */
    protected ?SequenceRule $rule = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SequenceCondition")
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

    public function getRule(): ?SequenceRule
    {
        return $this->rule;
    }

    public function setRule(?SequenceRule $rule): self
    {
        $this->rule = $rule;

        return $this;
    }

    public function getCondition(): ?SequenceCondition
    {
        return $this->condition;
    }

    public function setCondition(?SequenceCondition $condition): self
    {
        $this->condition = $condition;

        return $this;
    }
}
