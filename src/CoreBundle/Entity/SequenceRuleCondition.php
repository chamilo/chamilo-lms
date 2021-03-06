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
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceRule")
     * @ORM\JoinColumn(name="sequence_rule_id", referencedColumnName="id")
     */
    protected ?\Chamilo\CoreBundle\Entity\SequenceRule $rule = null;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceCondition")
     * @ORM\JoinColumn(name="sequence_condition_id", referencedColumnName="id")
     */
    protected ?\Chamilo\CoreBundle\Entity\SequenceCondition $condition = null;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @return SequenceRuleCondition
     */
    public function setRule(?SequenceRule $rule)
    {
        $this->rule = $rule;

        return $this;
    }

    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return SequenceRuleCondition
     */
    public function setCondition(?SequenceCondition $condition)
    {
        $this->condition = $condition;

        return $this;
    }
}
