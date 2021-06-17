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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return Platform
     */
    public function setId($id)
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
    public function getKid()
    {
        return $this->kid;
    }

    /**
     * Set kid.
     *
     * @param string $kid
     */
    public function setKid($kid)
    {
        $this->kid = $kid;
    }

    /**
     * Get privateKey.
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Set privateKey.
     *
     * @param string $privateKey
     *
     * @return Platform
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey(string $publicKey)
    {
        $this->publicKey = $publicKey;
    }


}
