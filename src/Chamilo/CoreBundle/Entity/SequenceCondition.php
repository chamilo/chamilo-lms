<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceCondition
 *
 * @ORM\Table(name="sequence_condition")
 * @ORM\Entity
 */
class SequenceCondition
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
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="mat_op", type="integer")
     */
    private $mathOperation;

    /**
     * @var string
     *
     * @ORM\Column(name="param", type="float")
     */
    private $param;

    /**
     * @var string
     *
     * @ORM\Column(name="act_true", type="integer")
     */
    private $actTrue;

    /**
     * @var string
     *
     * @ORM\Column(name="act_false", type="integer")
     */
    private $actFalse;

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
