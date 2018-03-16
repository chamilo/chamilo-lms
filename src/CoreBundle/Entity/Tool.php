<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tool.
 *
 * @ORM\Table(name="tool")
 * @ORM\Entity
 */
class Tool
{
    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode", mappedBy="tool", cascade={"persist", "remove"})
     */
    protected $resourceNodes;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\ToolResourceRights", mappedBy="tool", cascade={"persist", "remove"})
     */
    protected $toolResourceRights;
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true, unique=false)
     */
    private $image;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * @return ArrayCollection
     */
    public function getToolResourceRights()
    {
        return $this->toolResourceRights;
    }

    /**
     * @param ArrayCollection $toolResourceRights
     */
    public function setToolResourceRights($toolResourceRights)
    {
        $this->toolResourceRights = new ArrayCollection();

        foreach ($toolResourceRights as $toolResourceRight) {
            $this->addToolResourceRights($toolResourceRight);
        }
    }

    /**
     * @param ToolResourceRights $toolResourceRight
     *
     * @return $this
     */
    public function addToolResourceRights(ToolResourceRights $toolResourceRight)
    {
        $toolResourceRight->setTool($this);
        $this->toolResourceRights[] = $toolResourceRight;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResourceNodes()
    {
        return $this->resourceNodes;
    }

    /**
     * @param mixed $resourceNodes
     */
    public function setResourceNodes($resourceNodes)
    {
        $this->resourceNodes = $resourceNodes;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Tool
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set image.
     *
     * @param string $image
     *
     * @return Tool
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Tool
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
