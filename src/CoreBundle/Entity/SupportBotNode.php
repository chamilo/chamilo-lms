<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'support_bot_node')]
#[ORM\Index(columns: ['tree_id'], name: 'idx_support_bot_node_tree')]
#[ORM\HasLifecycleCallbacks]
class SupportBotNode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SupportBotTree::class, inversedBy: 'nodes')]
    #[ORM\JoinColumn(name: 'tree_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected SupportBotTree $tree;

    #[ORM\Column(name: 'content', type: 'text')]
    protected string $content = '';

    #[ORM\Column(name: 'sort_order', type: 'integer')]
    protected int $sortOrder = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    protected DateTime $updatedAt;

    /**
     * @var Collection<int, SupportBotOption>
     */
    #[ORM\OneToMany(mappedBy: 'node', targetEntity: SupportBotOption::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    protected Collection $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();
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

    public function getTree(): SupportBotTree
    {
        return $this->tree;
    }

    public function setTree(SupportBotTree $tree): self
    {
        $this->tree = $tree;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

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
     * @return Collection<int, SupportBotOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(SupportBotOption $option): self
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setNode($this);
        }

        return $this;
    }

    public function removeOption(SupportBotOption $option): self
    {
        $this->options->removeElement($option);

        return $this;
    }
}
