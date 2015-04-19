<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Sequence
 *
 * @ORM\Table(name="sequence")
 * @ORM\Entity
 */
class Sequence
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
     * @ORM\Column(name="is_part", type="boolean")
     */
    private $part;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceRowEntity")
     * @ORM\JoinColumn(name="sequence_row_entity_id", referencedColumnName="id")
     **/
    private $rowEntity;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceRowEntity")
     * @ORM\JoinColumn(name="sequence_row_entity_id_next", referencedColumnName="id")
     **/
    private $rowEntityNext;

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
