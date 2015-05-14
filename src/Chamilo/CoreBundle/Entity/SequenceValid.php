<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceRule
 *
 * @ORM\Table(name="sequence_valid")
 * @ORM\Entity
 */
class SequenceValid
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
     * @ORM\ManyToOne(targetEntity="SequenceVariable")
     * @ORM\JoinColumn(name="sequence_variable_id", referencedColumnName="id")
     **/
    private $variable;

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
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * @param mixed $variable
     *
     * @return SequenceValid
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;

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
     *
     * @return SequenceValid
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

}
