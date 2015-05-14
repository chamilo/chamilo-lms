<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceRuleCondition
 *
 * @ORM\Table(name="sequence_rule_condition")
 * @ORM\Entity
 */
class SequenceRuleCondition
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceRule")
     * @ORM\JoinColumn(name="sequence_rule_id", referencedColumnName="id")
     **/
    private $rule;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceCondition")
     * @ORM\JoinColumn(name="sequence_condition_id", referencedColumnName="id")
     **/
    private $condition;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param mixed $rule
     * @return SequenceRuleCondition
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param mixed $condition
     * @return SequenceRuleCondition
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }


}
