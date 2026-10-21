<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\SupportBotTreeRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: SupportBotTreeRepository::class)]
#[ORM\Table(name: 'support_bot_tree')]
#[ORM\Index(columns: ['status', 'access_url_id'], name: 'idx_support_bot_tree_status_url')]
#[ORM\HasLifecycleCallbacks]
class SupportBotTree
{
    public const string STATUS_ARCHIVED = 'archived';
    public const string STATUS_DRAFT = 'draft';
    public const string STATUS_PUBLISHED = 'published';

    private const array VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title = '';

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?AccessUrl $accessUrl = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20)]
    protected string $status = self::STATUS_DRAFT;

    #[ORM\Column(name: 'version', type: 'integer')]
    protected int $version = 1;

    #[ORM\ManyToOne(targetEntity: SupportBotNode::class)]
    #[ORM\JoinColumn(name: 'entry_node_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?SupportBotNode $entryNode = null;

    #[ORM\ManyToOne(targetEntity: TicketProject::class)]
    #[ORM\JoinColumn(name: 'default_ticket_project_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?TicketProject $defaultTicketProject = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: false)]
    protected User $createdBy;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    protected DateTime $updatedAt;

    /**
     * @var Collection<int, SupportBotNode>
     */
    #[ORM\OneToMany(mappedBy: 'tree', targetEntity: SupportBotNode::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    protected Collection $nodes;

    public function __construct()
    {
        $this->nodes = new ArrayCollection();
        $now = new DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAccessUrl(): ?AccessUrl
    {
        return $this->accessUrl;
    }

    public function setAccessUrl(?AccessUrl $accessUrl): self
    {
        $this->accessUrl = $accessUrl;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!\in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid support bot tree status.');
        }

        $this->status = $status;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = max(1, $version);

        return $this;
    }

    public function incrementVersion(): self
    {
        ++$this->version;

        return $this;
    }

    public function getEntryNode(): ?SupportBotNode
    {
        return $this->entryNode;
    }

    public function setEntryNode(?SupportBotNode $entryNode): self
    {
        $this->entryNode = $entryNode;

        return $this;
    }

    public function getDefaultTicketProject(): ?TicketProject
    {
        return $this->defaultTicketProject;
    }

    public function setDefaultTicketProject(?TicketProject $defaultTicketProject): self
    {
        $this->defaultTicketProject = $defaultTicketProject;

        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, SupportBotNode>
     */
    public function getNodes(): Collection
    {
        return $this->nodes;
    }

    public function addNode(SupportBotNode $node): self
    {
        if (!$this->nodes->contains($node)) {
            $this->nodes->add($node);
            $node->setTree($this);
        }

        return $this;
    }

    public function removeNode(SupportBotNode $node): self
    {
        $this->nodes->removeElement($node);

        if ($this->entryNode === $node) {
            $this->entryNode = null;
        }

        return $this;
    }
}
