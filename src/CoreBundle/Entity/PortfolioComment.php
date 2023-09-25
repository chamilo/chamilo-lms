<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Table(name: 'portfolio_comment')]
#[Gedmo\Tree(type: 'nested')]
class PortfolioComment
{
    public const VISIBILITY_VISIBLE = 1;
    public const VISIBILITY_PER_USER = 2;

    /**
     * Add @ to the next line if portfolio_advanced_sharing config setting is true
     * ORM\Column(name="visibility", type="smallint", options={"default": 1}).
     */
    protected int $visibility = 1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\ManyToOne(targetEntity: Portfolio::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Portfolio $item;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime')]
    private DateTime $date;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isImportant;

    #[Gedmo\TreeLeft]
    #[ORM\Column(type: 'integer')]
    private int $lft;

    #[Gedmo\TreeLevel]
    #[ORM\Column(type: 'integer')]
    private int $lvl;

    #[Gedmo\TreeRight]
    #[ORM\Column(type: 'integer')]
    private int $rgt;

    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?PortfolioComment $root;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?PortfolioComment $parent;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['lft' => 'DESC'])]
    private ArrayCollection $children;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $score;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isTemplate = false;

    public function __construct()
    {
        $this->isImportant = false;
        $this->children = new ArrayCollection();
        $this->visibility = 1;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getItem(): Portfolio
    {
        return $this->item;
    }

    public function setItem(Portfolio $item): self
    {
        $this->item = $item;

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

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): ArrayCollection
    {
        return $this->children;
    }

    public function setChildren(ArrayCollection $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function isImportant(): bool
    {
        return $this->isImportant;
    }

    public function setIsImportant(bool $isImportant): void
    {
        $this->isImportant = $isImportant;
    }

    public function getExcerpt(int $count = 190): string
    {
        return api_get_short_text_from_html($this->content, $count);
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): void
    {
        $this->score = $score;
    }

    public function getRoot(): self
    {
        return $this->root;
    }

    public function getLvl(): int
    {
        return $this->lvl;
    }

    public function isTemplate(): bool
    {
        return $this->isTemplate;
    }

    public function setIsTemplate(bool $isTemplate): self
    {
        $this->isTemplate = $isTemplate;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }
}
