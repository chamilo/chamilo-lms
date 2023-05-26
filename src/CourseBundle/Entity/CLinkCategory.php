<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CLinkCategory.
 */
#[ORM\Table(name: 'c_link_category')]
#[ORM\Entity(repositoryClass: \Chamilo\CourseBundle\Repository\CLinkCategoryRepository::class)]
class CLinkCategory extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'category_title', type: 'string', length: 255, nullable: false)]
    protected string $categoryTitle;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description;

    #[ORM\Column(name: 'display_order', type: 'integer', nullable: false)]
    protected int $displayOrder;

    /**
     * @var Collection|CLink[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CourseBundle\Entity\CLink::class, mappedBy: 'category')]
    protected Collection $links;

    public function __construct()
    {
        $this->description = '';
        $this->displayOrder = 0;
        $this->links = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getCategoryTitle();
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setCategoryTitle(string $categoryTitle): self
    {
        $this->categoryTitle = $categoryTitle;

        return $this;
    }

    public function getCategoryTitle(): string
    {
        return $this->categoryTitle;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    /**
     * @return CLink[]|Collection
     */
    public function getLinks(): array|Collection
    {
        return $this->links;
    }

    public function getResourceIdentifier(): int
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->getCategoryTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setCategoryTitle($name);
    }
}
