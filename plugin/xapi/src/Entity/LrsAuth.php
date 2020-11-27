<?php

/* For licensing terms, see /license.txt */

use Doctrine\ORM\Mapping as ORM;

/**
 * Class LrsAuth.
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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return LrsAuth
     */
    public function setUsername(string $username): LrsAuth
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return LrsAuth
     */
    public function setPassword(string $password): LrsAuth
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return LrsAuth
     */
    public function setEnabled(bool $enabled): LrsAuth
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return LrsAuth
     */
    public function setCreatedAt(DateTime $createdAt): LrsAuth
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
