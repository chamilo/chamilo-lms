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
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceRule")
     * @ORM\JoinColumn(name="sequence_rule_id", referencedColumnName="id")
     *
     * @var null|\Chamilo\CoreBundle\Entity\SequenceRule
     */
    protected $rule;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceCondition")
     * @ORM\JoinColumn(name="sequence_condition_id", referencedColumnName="id")
     *
     * @var null|\Chamilo\CoreBundle\Entity\SequenceCondition
     */
    protected $condition;

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
    public function setRule($rule)
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
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }
}
