<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SharedStatement.
 *
 * @package Chamilo\PluginBundle\Entity\XApi
 *
 * @ORM\Table(
 *     name="xapi_shared_statement",
 *     indexes={
 *         @ORM\Index(name="idx_datatype_dataid", columns={"data_type", "data_id"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idx_uuid", columns={"uuid"})
 *     }
 * )
 * @ORM\Entity()
 */
class SharedStatement
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="uuid", type="string")
     */
    private $uuid;
    /**
     * @var string
     *
     * @ORM\Column(name="data_type", type="string")
     */
    private $dataType;
    /**
     * @var int
     *
     * @ORM\Column(name="data_id", type="integer")
     */
    private $dataId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return SharedStatement
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return SharedStatement
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @param string $dataType
     *
     * @return SharedStatement
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;

        return $this;
    }

    /**
     * @return int
     */
    public function getDataId()
    {
        return $this->dataId;
    }

    /**
     * @param int $dataId
     *
     * @return SharedStatement
     */
    public function setDataId($dataId)
    {
        $this->dataId = $dataId;

        return $this;
    }
}
