<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\LtiProvider\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'plugin_lti_provider_platform')]
#[ORM\Entity]
class Platform
{
    #[ORM\Column(name: 'issuer', type: 'text')]
    public string $issuer;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id;

    #[ORM\Column(name: 'kid', type: 'string')]
    private string $kid;

    #[ORM\Column(name: 'client_id', type: 'text')]
    private string $clientId;

    #[ORM\Column(name: 'auth_login_url', type: 'text')]
    private string $authLoginUrl;

    #[ORM\Column(name: 'auth_token_url', type: 'text')]
    private string $authTokenUrl;

    #[ORM\Column(name: 'key_set_url', type: 'text')]
    private string $keySetUrl;

    #[ORM\Column(name: 'deployment_id', type: 'text')]
    private string $deploymentId;

    #[ORM\Column(name: 'tool_provider', type: 'text')]
    private string $toolProvider;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getToolProvider(): string
    {
        return $this->toolProvider;
    }

    public function setToolProvider(?string $toolProvider): void
    {
        $this->toolProvider = $toolProvider;
    }

    public function getKid(): string
    {
        return $this->kid;
    }

    public function setKid(string $kid): static
    {
        $this->kid = $kid;

        return $this;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function setIssuer(string $issuer): static
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): static
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get auth login URL.
     */
    public function getAuthLoginUrl()
    {
        return $this->authLoginUrl;
    }

    /**
     * Set auth login URL.
     */
    public function setAuthLoginUrl(string $authLoginUrl): static
    {
        $this->authLoginUrl = $authLoginUrl;

        return $this;
    }

    public function getAuthTokenUrl(): string
    {
        return $this->authTokenUrl;
    }

    public function setAuthTokenUrl(string $authTokenUrl): static
    {
        $this->authTokenUrl = $authTokenUrl;

        return $this;
    }

    public function getKeySetUrl(): string
    {
        return $this->keySetUrl;
    }

    public function setKeySetUrl(string $keySetUrl): static
    {
        $this->keySetUrl = $keySetUrl;

        return $this;
    }

    public function getDeploymentId(): string
    {
        return $this->deploymentId;
    }

    public function setDeploymentId(string $deploymentId): static
    {
        $this->deploymentId = $deploymentId;

        return $this;
    }
}
