<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\UserBundle\Entity\User;
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
 * ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class PortfolioComment
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;
    /**
     * @var \Chamilo\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=false)
     */
    private $author;
    /**
     * @var \Chamilo\CoreBundle\Entity\Portfolio
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Portfolio", inversedBy="comments")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false)
     */
    private $item;
    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;
    /**
     * @var int
     *
     * @Gedmo\TreeLeft()
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;
    /**
     * @var int
     *
     * @Gedmo\TreeLevel()
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;
    /**
     * @var int
     *
     * @Gedmo\TreeRight()
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;
    /**
     * @var \Chamilo\CoreBundle\Entity\PortfolioComment
     *
     * @Gedmo\TreeRoot()
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\PortfolioComment")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;
    /**
     * @var \Chamilo\CoreBundle\Entity\PortfolioComment|null
     *
     * @Gedmo\TreeParent()
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\PortfolioComment", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\PortfolioComment", mappedBy="parent")
     * @ORM\OrderBy({"lft"="DESC"})
     */
    private $children;

    /**
     * PortfolioComment constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \Chamilo\UserBundle\Entity\User
     */
    public function getAuthor(): User
    {
        return $this->author;
    }

    /**
     * @param \Chamilo\UserBundle\Entity\User $author
     *
     * @return PortfolioComment
     */
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
     *
     * @return PortfolioComment
     */
    public function setItem(Portfolio $item): PortfolioComment
    {
        $this->item = $item;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return PortfolioComment
     */
    public function setContent(string $content): PortfolioComment
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     *
     * @return PortfolioComment
     */
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
     *
     * @return PortfolioComment
     */
    public function setParent(?PortfolioComment $parent): PortfolioComment
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getChildren(): ArrayCollection
    {
        return $this->children;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $children
     *
     * @return PortfolioComment
     */
    public function setChildren(ArrayCollection $children): PortfolioComment
    {
        $this->children = $children;

        return $this;
    }
}
