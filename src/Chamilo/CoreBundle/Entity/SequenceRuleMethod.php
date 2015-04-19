<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceRuleMethod
 *
 * @ORM\Table(name="sequence_rule_method")
 * @ORM\Entity
 */
class SequenceRuleMethod
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
     * @var string
     *
     * @ORM\Column(name="method_order", type="integer")
     */
    private $methodOrder;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceRule")
     * @ORM\JoinColumn(name="sequence_rule_id", referencedColumnName="id")
     **/
    private $rule;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceMethod")
     * @ORM\JoinColumn(name="sequence_method_id", referencedColumnName="id")
     **/
    private $method;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
