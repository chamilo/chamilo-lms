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
 *         @ORM\Index(name="idx_uuid", columns={"uuid"})
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
     * @var string|null
     *
     * @ORM\Column(name="uuid", type="string", nullable=true)
     */
    private $uuid;
    /**
     * @var array
     *
     * @ORM\Column(name="statement", type="array")
     */
    private $statement;
    /**
     * @var bool
     *
     * @ORM\Column(name="sent", type="boolean", options={"default":false})
     */
    private $sent;

    /**
     * SharedStatement constructor.
     *
     * @param array $statement
     * @param null  $uuid
     * @param false $sent
     */
    public function __construct($statement, $uuid = null, $sent = false)
    {
        $this->statement = $statement;
        $this->uuid = $uuid;
        $this->sent = $sent;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): SharedStatement
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getStatement(): array
    {
        return $this->statement;
    }

    public function setStatement(array $statement): SharedStatement
    {
        $this->statement = $statement;

        return $this;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function setSent(bool $sent): SharedStatement
    {
        $this->sent = $sent;

        return $this;
    }
}
