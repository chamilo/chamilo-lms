<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CThematicPlan.
 *
 * @ORM\Table(
 *  name="c_thematic_plan",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="thematic_id", columns={"thematic_id", "description_type"})
 *  }
 * )
 * @ORM\Entity
 */
class CThematicPlan //extends AbstractResource implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var CThematic
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CThematic", inversedBy="plans")
     * @ORM\JoinColumn(name="thematic_id", referencedColumnName="iid")
     */
    protected $thematic;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="description_type", type="integer", nullable=false)
     */
    protected $descriptionType;

    public function __toString(): string
    {
        return (string) $this->getIid();
    }

    /**
     * Set thematicId.
     *
     * @param int $thematicId
     *
     * @return CThematicPlan
     */
    public function setThematicId($thematicId)
    {
        $this->thematicId = $thematicId;

        return $this;
    }

    /**
     * Get thematicId.
     *
     * @return int
     */
    public function getThematicId()
    {
        return $this->thematicId;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CThematicPlan
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CThematicPlan
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

    /**
     * Set descriptionType.
     *
     * @param int $descriptionType
     *
     * @return CThematicPlan
     */
    public function setDescriptionType($descriptionType)
    {
        $this->descriptionType = $descriptionType;

        return $this;
    }

    /**
     * Get descriptionType.
     *
     * @return int
     */
    public function getDescriptionType()
    {
        return $this->descriptionType;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CThematicPlan
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function getThematic(): CThematic
    {
        return $this->thematic;
    }

    public function setThematic(CThematic $thematic): self
    {
        $this->thematic = $thematic;

        return $this;
    }

    /*
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }*/
}
