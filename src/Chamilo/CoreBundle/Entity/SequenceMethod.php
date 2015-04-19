<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceMethod
 *
 * @ORM\Table(name="sequence_method")
 * @ORM\Entity
 */
class SequenceMethod
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
     * @ORM\Column(name="formula", type="text")
     */
    private $formula;

    /**
     * @var string
     *
     * @ORM\Column(name="assign", type="integer")
     */
    private $assign;

    /**
     * @var string
     *
     * @ORM\Column(name="met_type", type="integer")
     */
    private $metType;

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
