<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="c_wiki_category")
 * Add @ to the next line if api_get_configuration_value('wiki_categories_enabled') is true
 * ORM\Entity(repositoryClass="Chamilo\CourseBundle\Entity\Repository\CWikiCategoryRepository")
 */
class CWikiCategory
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="id", type="integer")
     */
    private $id;
    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    private $name;
    /**
     * @var Collection<int, CWiki>
     * @ORM\ManyToMany(targetEntity="Chamilo\CourseBundle\Entity\CWiki", mappedBy="categories")
     */
    private $wikiPages;
    /**
     * @var Course
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $course;
    /**
     * @var Session|null
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $session;
    /**
     * @var int|null
     * @Gedmo\TreeLeft()
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;
    /**
     * @var int|null
     * @Gedmo\TreeLevel()
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;
    /**
     * @var int|null
     * @Gedmo\TreeRight()
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;
    /**
     * @var CWikiCategory|null
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CWikiCategory")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;
    /**
     * @var CWikiCategory|null
     * @Gedmo\TreeParent()
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CWikiCategory", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;
    /**
     * @var Collection<int, CWikiCategory>
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CWikiCategory", mappedBy="parent")
     * @ORM\OrderBy({"lft"="ASC"})
     */
    private $children;

    public function __construct()
    {
        $this->parent = null;
        $this->children = new ArrayCollection();
        $this->wikiPages = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNodeName(): string
    {
        return str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $this->lvl).$this->name;
    }

    public function setName(string $name): CWikiCategory
    {
        $this->name = $name;

        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): CWikiCategory
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): CWikiCategory
    {
        $this->session = $session;

        return $this;
    }

    public function getRoot(): ?CWikiCategory
    {
        return $this->root;
    }

    public function getParent(): ?CWikiCategory
    {
        return $this->parent;
    }

    public function setParent(?CWikiCategory $parent): CWikiCategory
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): CWikiCategory
    {
        $this->children = $children;

        return $this;
    }

    public function addWikiPage(CWiki $page): CWikiCategory
    {
        $this->wikiPages->add($page);

        return $this;
    }

    public function getWikiPages(): Collection
    {
        return $this->wikiPages;
    }

    public function getLvl(): ?int
    {
        return $this->lvl;
    }
}
