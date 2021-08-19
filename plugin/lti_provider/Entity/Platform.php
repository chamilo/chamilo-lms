<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\LtiProvider;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Platform.
 *
 * @package Chamilo\PluginBundle\Entity\LtiProvider
 *
 * @ORM\Table(name="plugin_lti_provider_platform")
 * @ORM\Entity()
 */
class Platform
{
    /**
     * @var string
     *
     * @ORM\Column(name="issuer", type="text")
     */
    public $issuer;
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
     * @ORM\Column(name="client_id", type="text")
     */
    private $clientId;
    /**
     * @var string
     *
     * @ORM\Column(name="auth_login_url", type="text")
     */
    private $authLoginUrl;
    /**
     * @var string
     *
     * @ORM\Column(name="auth_token_url", type="text")
     */
    private $authTokenUrl;
    /**
     * @var string
     *
     * @ORM\Column(name="key_set_url", type="text")
     */
    private $keySetUrl;
    /**
     * @var string
     *
     * @ORM\Column(name="deployment_id", type="text")
     */
    private $deploymentId;

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
    public function setId(int $id): Platform
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
    public function setKid(string $kid): Platform
    {
        $this->kid = $kid;

        return $this;
    }

    /**
     * Get Issuer.
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * Set issuer.
     */
    public function setIssuer(string $issuer): Platform
    {
        $this->issuer = $issuer;

        return $this;
    }

    /**
     * Get client ID.
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Set client ID.
     */
    public function setClientId(string $clientId): Platform
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get auth login URL.
     */
    public function getAuthLoginUrl(): string
    {
        return $this->authLoginUrl;
    }

    /**
     * Set auth login URL.
     */
    public function setAuthLoginUrl(string $authLoginUrl): Platform
    {
        $this->authLoginUrl = $authLoginUrl;

        return $this;
    }

    /**
     * Get auth token URL.
     */
    public function getAuthTokenUrl(): string
    {
        return $this->authTokenUrl;
    }

    /**
     * Set auth token URL.
     */
    public function setAuthTokenUrl(string $authTokenUrl): Platform
    {
        $this->authTokenUrl = $authTokenUrl;

        return $this;
    }

    /**
     * Get key set URL.
     */
    public function getKeySetUrl(): string
    {
        return $this->keySetUrl;
    }

    /**
     * Set key set URL.
     */
    public function setKeySetUrl(string $keySetUrl): Platform
    {
        $this->keySetUrl = $keySetUrl;

        return $this;
    }

    /**
     * Get Deployment ID.
     */
    public function getDeploymentId(): string
    {
        return $this->deploymentId;
    }

    /**
     * Set Deployment ID.
     */
    public function setDeploymentId(string $deploymentId): Platform
    {
        $this->deploymentId = $deploymentId;

        return $this;
    }
}
