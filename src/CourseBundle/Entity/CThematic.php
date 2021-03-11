<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CThematic.
 *
 * @ORM\Table(
 *     name="c_thematic",
 *     indexes={
 *         @ORM\Index(name="active", columns={"active"})
 *     }
 * )
 * @ORM\Entity
 */
class CThematic extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected ?string $content = null;

    /**
     * @ORM\Column(name="display_order", type="integer", nullable=false)
     */
    protected int $displayOrder;

    /**
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    protected bool $active;

    /**
     * @var ArrayCollection|CThematicPlan[]
     *
     * @ORM\OneToMany(
     *     targetEntity="CThematicPlan", mappedBy="thematic", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected Collection $plans;

    /**
     * @var Collection|CThematicAdvance[]
     *
     * @ORM\OrderBy({"startDate" = "ASC"})
     *
     * @ORM\OneToMany(
     *     targetEntity="CThematicAdvance", mappedBy="thematic", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected Collection $advances;

    public function __construct()
    {
        $this->plans = new ArrayCollection();
        $this->advances = new ArrayCollection();
        $this->active = true;
    }

    public function __toString(): string
    {
        return $this->getTitle();
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

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setDisplayOrder(int $displayOrder): self
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

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * @return ArrayCollection|CThematicPlan[]
     */
    public function getPlans()
    {
        return $this->plans;
    }

    /**
     * @return ArrayCollection|CThematicAdvance[]
     */
    public function getAdvances()
    {
        return $this->advances;
    }

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
    }
}
