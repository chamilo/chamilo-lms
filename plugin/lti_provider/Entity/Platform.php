<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\LtiProvider;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Platform
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
     *
     * @return int
     */
    public function getId(): int
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
    public function setId(int $id): Platform
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
     * Get Issuer
     *
     * @return string
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * Set issuer
     *
     * @param string $issuer
     */
    public function setIssuer(string $issuer)
    {
        $this->issuer = $issuer; 

        return $this;
    }

    /**
     * Get client ID
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Set client ID
     *
     * @param string $clientId
     */
    public function setClientId(string $clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get auth login URL
     *
     * @return string
     */
    public function getAuthLoginUrl(): string
    {
        return $this->authLoginUrl;
    }

    /**
     * Set auth login URL
     *
     * @param string $authLoginUrl
     */
    public function setAuthLoginUrl(string $authLoginUrl)
    {
        $this->authLoginUrl = $authLoginUrl;

        return $this;
    }

    /**
     * Get auth token URL
     *
     * @return string
     */
    public function getAuthTokenUrl(): string
    {
        return $this->authTokenUrl;
    }

    /**
     * Set auth token URL
     *
     * @param string $authTokenUrl
     */
    public function setAuthTokenUrl(string $authTokenUrl)
    {
        $this->authTokenUrl = $authTokenUrl;

        return $this;
    }

    /**
     * Get key set URL
     *
     * @return string
     */
    public function getKeySetUrl(): string
    {
        return $this->keySetUrl;
    }

    /**
     * Set key set URL
     *
     * @param string $keySetUrl
     */
    public function setKeySetUrl(string $keySetUrl)
    {
        $this->keySetUrl = $keySetUrl;

        return $this;
    }

    /**
     * Get Deployment ID
     *
     * @return string
     */
    public function getDeploymentId(): string
    {
        return $this->deploymentId;
    }

    /**
     * Set Deployment ID
     *
     * @param string $deploymentId
     */
    public function setDeploymentId(string $deploymentId)
    {
        $this->deploymentId = $deploymentId;

        return $this;
    }
}
