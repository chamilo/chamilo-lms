<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\ImsLti;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'plugin_ims_lti_token')]
#[ORM\Entity]
class Token
{
    public const TOKEN_LIFETIME = 3600;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id;

    #[ORM\ManyToOne(targetEntity: ImsLtiTool::class)]
    #[ORM\JoinColumn(name: 'tool_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ImsLtiTool $tool;

    #[ORM\Column(name: 'scope', type: 'json')]
    private array $scope;

    #[ORM\Column(name: 'hash', type: 'string')]
    private string $hash;

    #[ORM\Column(name: 'created_at', type: 'integer')]
    private int $createdAt;

    #[ORM\Column(name: 'expires_at', type: 'integer')]
    private int $expiresAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTool(): ImsLtiTool
    {
        return $this->tool;
    }

    public function setTool(ImsLtiTool $tool): static
    {
        $this->tool = $tool;

        return $this;
    }

    public function getScope(): array
    {
        return $this->scope;
    }

    public function setScope(array $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): static
    {
        $this->hash = $hash;

        return $this;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(int $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getScopeInString(): string
    {
        return implode(' ', $this->scope);
    }

    public function generateHash(): static
    {
        $this->hash = sha1(uniqid(mt_rand()));

        return $this;
    }
}
