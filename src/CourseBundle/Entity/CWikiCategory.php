<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Repository\CWikiCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'c_wiki_category')]
#[ORM\Entity(repositoryClass: CWikiCategoryRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class CWikiCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string')]
    private string $title;

    #[ORM\ManyToMany(targetEntity: CWiki::class, mappedBy: 'categories')]
    private Collection $wikiPages;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Session $session;

    #[Gedmo\TreeLeft]
    #[ORM\Column(name: 'lft', type: 'integer')]
    private int $lft = 0;

    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: 'integer')]
    private int $lvl = 0;

    #[Gedmo\TreeRight]
    #[ORM\Column(name: 'rgt', type: 'integer')]
    private int $rgt = 0;

    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?CWikiCategory $root = null;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?CWikiCategory $parent;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    private Collection $children;

    public function __construct()
    {
        $this->session = null;
        $this->parent = null;
        $this->children = new ArrayCollection();
        $this->wikiPages = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getNodeName(): string
    {
        return str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $this->lvl).$this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Legacy compatibility for the existing Wiki category form.
     */
    public function getName(): string
    {
        return $this->getTitle();
    }

    /**
     * Legacy compatibility for the existing Wiki category form.
     */
    public function setName(string $name): self
    {
        return $this->setTitle($name);
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getRoot(): ?self
    {
        return $this->root;
    }

    public function getLeft(): int
    {
        return $this->lft;
    }

    public function getLevel(): int
    {
        return $this->lvl;
    }

    public function getRight(): int
    {
        return $this->rgt;
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

    /**
     * @return Collection<int, CWikiCategory>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function addWikiPage(CWiki $page): self
    {
        if (!$this->wikiPages->contains($page)) {
            $this->wikiPages->add($page);
        }

        return $this;
    }

    public function removeWikiPage(CWiki $page): self
    {
        $this->wikiPages->removeElement($page);

        return $this;
    }

    /**
     * @return Collection<int, CWiki>
     */
    public function getWikiPages(): Collection
    {
        return $this->wikiPages;
    }
}
