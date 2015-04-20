<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceRowEntity
 *
 * @ORM\Table(name="sequence_row_entity")
 * @ORM\Entity
 */
class SequenceRowEntity
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
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer")
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="row_id", type="integer")
     */
    private $rowId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceTypeEntity")
     * @ORM\JoinColumn(name="sequence_type_entity_id", referencedColumnName="id")
     **/
    private $type;

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
