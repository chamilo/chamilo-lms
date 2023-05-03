<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class PortfolioComment.
 *
 * @package Chamilo\CoreBundle\Entity
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="portfolio_comment")
 * Add @ to the next line if api_get_configuration_value('allow_portfolio_tool') is true
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\PortfolioCommentRepository")
 */
class PortfolioComment
{
    public const VISIBILITY_VISIBLE = 1;
    public const VISIBILITY_PER_USER = 2;

    /**
     * @ORM\Column(name="visibility", type="smallint", options={"default": 1})
     */
    protected $visibility = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $author;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Portfolio", inversedBy="comments")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $item;

    /**
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(name="is_important", type="boolean", options={"default":false})
     */
    private $isImportant;

    /**
     * @Gedmo\TreeLeft()
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeLevel()
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;

    /**
     * @Gedmo\TreeRight()
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeRoot()
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\PortfolioComment")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;

    /**
     * @Gedmo\TreeParent()
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\PortfolioComment", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\PortfolioComment", mappedBy="parent")
     * @ORM\OrderBy({"lft"="DESC"})
     */
    private $children;

    /**
     * @ORM\Column(name="score", type="float", nullable=true)
     */
    private $score;

    /**
     * @ORM\Column(name="is_template", type="boolean", options={"default": false})
     */
    private $isTemplate = false;

    /**
     * PortfolioComment constructor.
     */
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

    public function setAuthor(User $author): PortfolioComment
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return \Chamilo\CoreBundle\Entity\Portfolio
     */
    public function getItem(): Portfolio
    {
        return $this->item;
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\Portfolio $item
     */
    public function setItem(Portfolio $item): PortfolioComment
    {
        $this->item = $item;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): PortfolioComment
    {
        $this->content = $content;

        return $this;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): PortfolioComment
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return \Chamilo\CoreBundle\Entity\PortfolioComment|null
     */
    public function getParent(): ?PortfolioComment
    {
        return $this->parent;
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\PortfolioComment|null $parent
     */
    public function setParent(?PortfolioComment $parent): PortfolioComment
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): ArrayCollection
    {
        return $this->children;
    }

    public function setChildren(ArrayCollection $children): PortfolioComment
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

    /**
     * @return \Chamilo\CoreBundle\Entity\PortfolioComment
     */
    public function getRoot(): PortfolioComment
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

    public function setIsTemplate(bool $isTemplate): PortfolioComment
    {
        $this->isTemplate = $isTemplate;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): PortfolioComment
    {
        $this->visibility = $visibility;

        return $this;
    }
}
