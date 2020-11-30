<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Cmi5Item.
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="xapi_cmi5_item")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Cmi5Item
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string")
     */
    private $identifier;
    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string")
     */
    private $type;
    /**
     * @var array;
     *
     * @ORM\Column(name="title", type="json")
     */
    private $title;
    /**
     * @var array
     *
     * @ORM\Column(name="description", type="json")
     */
    private $description;
    /**
     * @var string|null
     *
     * @ORM\Column(name="url", type="string", nullable=true)
     */
    private $url;
    /**
     * @var string|null
     *
     * @ORM\Column(name="activity_type", type="string", nullable=true)
     */
    private $activityType;
    /**
     * @var string|null
     *
     * @ORM\Column(name="launch_method", type="string", nullable=true)
     */
    private $launchMethod;
    /**
     * @var string|null
     *
     * @ORM\Column(name="move_on", type="string", nullable=true)
     */
    private $moveOn;
    /**
     * @var float|null
     *
     * @ORM\Column(name="mastery_score", type="float", nullable=true)
     */
    private $masteryScore;
    /**
     * @var string|null
     *
     * @ORM\Column(name="launch_parameters", type="string", nullable=true)
     */
    private $launchParameters;
    /**
     * @var string|null
     *
     * @ORM\Column(name="entitlement_key", type="string", nullable=true)
     */
    private $entitlementKey;
    /**
     * @var string|null
     *
     * @ORM\Column(name="status", type="string", nullable=true)
     */
    private $status;
    /**
     * @var \Chamilo\PluginBundle\Entity\XApi\ToolLaunch
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\ToolLaunch")
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $tool;

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
     * @var \Chamilo\PluginBundle\Entity\XApi\Cmi5Item
     *
     * @Gedmo\TreeRoot()
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Cmi5Item")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;
    /**
     * @var \Chamilo\PluginBundle\Entity\XApi\Cmi5Item|null
     *
     * @Gedmo\TreeParent()
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Cmi5Item", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\PluginBundle\Entity\XApi\Cmi5Item", mappedBy="parent")
     * @ORM\OrderBy({"lft"="ASC"})
     */
    private $children;

    /**
     * Cmi5Item constructor.
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
     * @param int $id
     *
     * @return Cmi5Item
     */
    public function setId(int $id): Cmi5Item
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return Cmi5Item
     */
    public function setIdentifier(string $identifier): Cmi5Item
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Cmi5Item
     */
    public function setType(string $type): Cmi5Item
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getTitle(): array
    {
        return $this->title;
    }

    /**
     * @param array $title
     *
     * @return Cmi5Item
     */
    public function setTitle(array $title): Cmi5Item
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return array
     */
    public function getDescription(): array
    {
        return $this->description;
    }

    /**
     * @param array $description
     *
     * @return Cmi5Item
     */
    public function setDescription(array $description): Cmi5Item
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     *
     * @return Cmi5Item
     */
    public function setUrl(?string $url): Cmi5Item
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getActivityType(): ?string
    {
        return $this->activityType;
    }

    /**
     * @param string|null $activityType
     *
     * @return Cmi5Item
     */
    public function setActivityType(?string $activityType): Cmi5Item
    {
        $this->activityType = $activityType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLaunchMethod(): ?string
    {
        return $this->launchMethod;
    }

    /**
     * @param string|null $launchMethod
     *
     * @return Cmi5Item
     */
    public function setLaunchMethod(?string $launchMethod): Cmi5Item
    {
        $this->launchMethod = $launchMethod;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMoveOn(): ?string
    {
        return $this->moveOn;
    }

    /**
     * @param string|null $moveOn
     *
     * @return Cmi5Item
     */
    public function setMoveOn(?string $moveOn): Cmi5Item
    {
        $this->moveOn = $moveOn;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getMasteryScore(): ?float
    {
        return $this->masteryScore;
    }

    /**
     * @param float|null $masteryScore
     *
     * @return Cmi5Item
     */
    public function setMasteryScore(?float $masteryScore): Cmi5Item
    {
        $this->masteryScore = $masteryScore;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLaunchParameters(): ?string
    {
        return $this->launchParameters;
    }

    /**
     * @param string|null $launchParameters
     *
     * @return Cmi5Item
     */
    public function setLaunchParameters(?string $launchParameters): Cmi5Item
    {
        $this->launchParameters = $launchParameters;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEntitlementKey(): ?string
    {
        return $this->entitlementKey;
    }

    /**
     * @param string|null $entitlementKey
     *
     * @return Cmi5Item
     */
    public function setEntitlementKey(?string $entitlementKey): Cmi5Item
    {
        $this->entitlementKey = $entitlementKey;

        return $this;
    }

    /**
     * @return \Chamilo\PluginBundle\Entity\XApi\Cmi5Item|null
     */
    public function getParent(): ?Cmi5Item
    {
        return $this->parent;
    }

    /**
     * @param \Chamilo\PluginBundle\Entity\XApi\Cmi5Item|null $parent
     *
     * @return Cmi5Item
     */
    public function setParent(?Cmi5Item $parent): Cmi5Item
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
     * @return Cmi5Item
     */
    public function setChildren(ArrayCollection $children): Cmi5Item
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     *
     * @return Cmi5Item
     */
    public function setStatus(?string $status): Cmi5Item
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \Chamilo\PluginBundle\Entity\XApi\ToolLaunch
     */
    public function getTool(): ToolLaunch
    {
        return $this->tool;
    }

    /**
     * @param \Chamilo\PluginBundle\Entity\XApi\ToolLaunch $tool
     *
     * @return Cmi5Item
     */
    public function setTool(ToolLaunch $tool): Cmi5Item
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * @return \Chamilo\PluginBundle\Entity\XApi\Cmi5Item
     */
    public function getRoot(): Cmi5Item
    {
        return $this->root;
    }
}
