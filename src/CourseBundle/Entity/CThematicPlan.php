<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="c_thematic_plan",
 *     indexes={
 *         @ORM\Index(name="thematic_id", columns={"thematic_id", "description_type"})
 *     }
 * )
 * @ORM\Entity
 */
class CThematicPlan //extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    #[Assert\NotBlank]
    protected string $title;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CThematic", inversedBy="plans")
     * @ORM\JoinColumn(name="thematic_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CThematic $thematic;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="description_type", type="integer", nullable=false)
     */
    protected int $descriptionType;

    public function __toString(): string
    {
        return (string) $this->getIid();
    }

    public function setTitle(string $title): self
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

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescriptionType(int $descriptionType): self
    {
        $this->descriptionType = $descriptionType;

        return $this;
    }

    public function getDescriptionType(): int
    {
        return $this->descriptionType;
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
