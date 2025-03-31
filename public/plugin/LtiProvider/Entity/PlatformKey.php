<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\LtiProvider\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'plugin_lti_provider_platform_key')]
#[ORM\Entity]
class PlatformKey
{
    #[ORM\Column(name: 'public_key', type: 'text')]
    public string $publicKey;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id;

    #[ORM\Column(name: 'kid', type: 'string')]
    private string $kid;

    #[ORM\Column(name: 'private_key', type: 'text')]
    private string $privateKey;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(string $privateKey): static
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): static
    {
        $this->publicKey = $publicKey;

        return $this;
    }
}
