<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class LrsAuth.
 *
 * @package Chamilo\PluginBundle\Entity\XApi
 *
 * @ORM\Table(name="xapi_lrs_auth")
 * @ORM\Entity()
 */
class LrsAuth
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
     * @ORM\Column(name="username", type="string")
     */
    private $username;
    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string")
     */
    private $password;
    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): LrsAuth
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): LrsAuth
    {
        $this->password = $password;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): LrsAuth
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): LrsAuth
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
