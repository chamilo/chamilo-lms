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
     * Get Issuer
     *
     * @return string
     */
    public function getIssuer()
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
    }

    /**
     * Get client ID
     *
     * @return string
     */
    public function getClientId()
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
    }

    /**
     * Get auth login URL
     *
     * @return string
     */
    public function getAuthLoginUrl()
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
    }

    /**
     * Get auth token URL
     *
     * @return string
     */
    public function getAuthTokenUrl()
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
    }

    /**
     * Get key set URL
     * @return string
     */
    public function getKeySetUrl()
    {
        return $this->keySetUrl;
    }

    /**
     * Set key set URL
     * @param string $keySetUrl
     */
    public function setKeySetUrl(string $keySetUrl)
    {
        $this->keySetUrl = $keySetUrl;
    }

    /**
     * Get Deployment ID
     *
     * @return string
     */
    public function getDeploymentId()
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


}
