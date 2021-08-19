<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\LtiProvider;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class PlatformKey.
 *
 * @package Chamilo\PluginBundle\Entity\LtiProvider
 *
 * @ORM\Table(name="plugin_lti_provider_platform_key")
 * @ORM\Entity()
 */
class PlatformKey
{
    /**
     * @var string
     *
     * @ORM\Column(name="public_key", type="text")
     */
    public $publicKey;
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    protected $id;
    /**
     * @var string
     *
     * @ORM\Column(name="kid", type="string")
     */
    private $kid;
    /**
     * @var string
     *
     * @ORM\Column(name="private_key", type="text")
     */
    private $privateKey;

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set id.
     */
    public function setId(int $id): PlatformKey
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get key id.
     */
    public function getKid(): string
    {
        return $this->kid;
    }

    /**
     * Set key id.
     */
    public function setKid(string $kid): PlatformKey
    {
        $this->kid = $kid;

        return $this;
    }

    /**
     * Get privateKey.
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * Set privateKey.
     */
    public function setPrivateKey(string $privateKey): PlatformKey
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Get publicKey.
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Set publicKey.
     */
    public function setPublicKey(string $publicKey): PlatformKey
    {
        $this->publicKey = $publicKey;

        return $this;
    }
}
