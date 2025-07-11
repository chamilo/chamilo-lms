<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);


namespace Chamilo\PluginBundle\StudentFollowUp\Entity;

use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'sfu_post')]
#[ORM\Entity]
#[Gedmo\Tree(type: 'nested')]
class CarePost
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'content', type: 'text', nullable: true)]
    protected string $content;

    #[ORM\Column(name: 'external_care_id', type: 'string', nullable: true)]
    protected ?string $externalCareId;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected ?DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    protected ?DateTime $updatedAt;

    #[ORM\Column(name: 'private', type: 'boolean')]
    protected bool $private;

    #[ORM\Column(name: 'external_source', type: 'boolean')]
    protected bool $externalSource;

    #[ORM\Column(name: 'tags', type: 'array')]
    protected array $tags;

    #[ORM\Column(name: 'attachment', type: 'string', length: 255)]
    protected string $attachment;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'insert_user_id', referencedColumnName: 'id', nullable: false)]
    private User $insertUser;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: CarePost::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private CarePost $parent;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: CarePost::class)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $children;

    #[Gedmo\TreeLeft]
    #[ORM\Column(name: 'lft', type: 'integer', unique: false, nullable: true)]
    private ?int $lft;

    #[Gedmo\TreeRight]
    #[ORM\Column(name: 'rgt', type: 'integer', unique: false, nullable: true)]
    private ?int $rgt;

    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: 'integer', unique: false, nullable: true)]
    private ?int $lvl;

    #[Gedmo\TreeRoot]
    #[ORM\Column(name: 'root', type: 'integer', unique: false, nullable: true)]
    private ?int $root;

    /**
     * Project constructor.
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->attachment = '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle($title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getExternalCareId(): ?string
    {
        return $this->externalCareId;
    }

    public function setExternalCareId(?string $externalCareId): static
    {
        $this->externalCareId = $externalCareId;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser($user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): static
    {
        $this->private = $private;

        return $this;
    }

    public function isExternalSource(): bool
    {
        return $this->externalSource;
    }

    public function setExternalSource(bool $externalSource): static
    {
        $this->externalSource = $externalSource;

        return $this;
    }

    public function getAttachment(): string
    {
        return $this->attachment;
    }

    public function setAttachment(string $attachment): static
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function getParent(): CarePost
    {
        return $this->parent;
    }

    public function setParent(CarePost $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function hasParent(): int
    {
        return !empty($this->parent) ? 1 : 0;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): static
    {
        $this->children = $children;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function getInsertUser(): User
    {
        return $this->insertUser;
    }

    public function setInsertUser(User $insertUser): static
    {
        $this->insertUser = $insertUser;

        return $this;
    }
}
