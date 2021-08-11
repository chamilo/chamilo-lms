<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\LtiProvider;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Platform
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
     *
     * @return int
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
     *
     * Get kid.
     *
     * @return string
     */
    public function getKid(): string
    {
        return $this->kid;
    }

    /**
     * Set kid.
     *
     * @param string $kid
     */
    public function setKid(string $kid)
    {
        $this->kid = $kid;

        return $this;
    }

    /**
     * Get privateKey.
     *
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * Set privateKey.
     *
     * @param string $privateKey
     *
     * @return PlatformKey
     */
    public function setPrivateKey(string $privateKey): PlatformKey
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey(string $publicKey)
    {
        $this->publicKey = $publicKey;

        return $this;
    }
}
