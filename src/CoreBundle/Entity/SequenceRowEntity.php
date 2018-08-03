<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceRowEntity.
 *
 * @ORM\Table(name="sequence_row_entity")
 * @ORM\Entity
 */
class SequenceRowEntity
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
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer")
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="row_id", type="integer")
     */
    protected $rowId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceTypeEntity")
     * @ORM\JoinColumn(name="sequence_type_entity_id", referencedColumnName="id")
     */
    protected $type;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * @param int $cId
     *
     * @return SequenceRowEntity
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param int $sessionId
     *
     * @return SequenceRowEntity
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @return int
     */
    public function getRowId()
    {
        return $this->rowId;
    }

    /**
     * @param int $rowId
     *
     * @return SequenceRowEntity
     */
    public function setRowId($rowId)
    {
        $this->rowId = $rowId;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return SequenceRowEntity
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return SequenceRowEntity
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
