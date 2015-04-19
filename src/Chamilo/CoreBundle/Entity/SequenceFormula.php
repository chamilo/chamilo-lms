<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceFormula
 *
 * @ORM\Table(name="sequence_formula")
 * @ORM\Entity
 */
class SequenceFormula
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
     * @ORM\ManyToOne(targetEntity="SequenceMethod")
     * @ORM\JoinColumn(name="sequence_method_id", referencedColumnName="id")
     **/
    private $method;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceVariable")
     * @ORM\JoinColumn(name="sequence_variable_id", referencedColumnName="id")
     **/
    private $variable;


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
