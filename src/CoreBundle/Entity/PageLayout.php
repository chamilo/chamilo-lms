<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * PageLayout entity.
 */
#[ApiResource(
    normalizationContext: ['groups' => ['page_layout:read']],
    denormalizationContext: ['groups' => ['page_layout:write']]
)]
#[ORM\Table(name: 'page_layout')]
#[ORM\Entity]
class PageLayout
{
    #[Groups(['page_layout:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[Groups(['page_layout:read', 'page_layout:write'])]
    #[ORM\Column(name: 'url', type: 'text', nullable: false)]
    private string $url;

    #[Groups(['page_layout:read', 'page_layout:write'])]
    #[ORM\Column(name: 'roles', type: 'text', nullable: true)]
    private ?string $roles = null;

    #[Groups(['page_layout:read', 'page_layout:write'])]
    #[ORM\ManyToOne(targetEntity: PageLayoutTemplate::class)]
    #[ORM\JoinColumn(name: 'page_layout_template_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PageLayoutTemplate $pageLayoutTemplate = null;

    #[Groups(['page_layout:read', 'page_layout:write'])]
    #[ORM\Column(name: 'layout', type: 'text', nullable: false)]
    private string $layout;

    #[Groups(['page_layout:read'])]
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?DateTimeInterface $createdAt = null;

    #[Groups(['page_layout:read'])]
    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[Groups(['page_layout:read'])]
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[Groups(['page_layout:read'])]
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'updated_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $updatedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getRoles(): ?string
    {
        return $this->roles;
    }

    public function setRoles(?string $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPageLayoutTemplate(): ?PageLayoutTemplate
    {
        return $this->pageLayoutTemplate;
    }

    public function setPageLayoutTemplate(?PageLayoutTemplate $template): self
    {
        $this->pageLayoutTemplate = $template;

        return $this;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function setLayout(string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $user): self
    {
        $this->createdBy = $user;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $user): self
    {
        $this->updatedBy = $user;

        return $this;
    }
}
