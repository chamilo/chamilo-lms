<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CGlossary.
 *
 * @ORM\Table(
 *  name="c_glossary"
 * )
 * @ORM\Entity
 */
class CGlossary extends AbstractResource implements ResourceInterface
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
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected ?string $description;

    /**
     * @var int
     *
     * @ORM\Column(name="display_order", type="integer", nullable=true)
     */
    protected $displayOrder;

    /**
     * Set name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set description.
     */
    public function setDescription(string $description): self
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
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return CGlossary
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set glossaryId.
     *
     * @param int $glossaryId
     *
     * @return CGlossary
     */
    public function setGlossaryId($glossaryId)
    {
        $this->glossaryId = $glossaryId;

        return $this;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }

    public function setResourceName(string $name): self
    {
        return $this->setName($name);
    }

    public function __toString(): string
    {
        return $this->getName();
    }

}
