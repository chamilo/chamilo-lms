<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_forum_category')]
#[ORM\Entity(repositoryClass: CForumCategoryRepository::class)]
class CForumCategory extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'cat_title', type: 'string', length: 255, nullable: false)]
    protected string $catTitle;

    #[ORM\Column(name: 'cat_comment', type: 'text', nullable: true)]
    protected ?string $catComment;

    #[ORM\Column(name: 'cat_order', type: 'integer', nullable: false)]
    protected int $catOrder;

    #[ORM\Column(name: 'locked', type: 'integer', nullable: false)]
    protected int $locked;

    /**
     * @var Collection<int, CForum>
     */
    #[ORM\OneToMany(mappedBy: 'forumCategory', targetEntity: CForum::class)]
    protected Collection $forums;

    public function __construct()
    {
        $this->catComment = '';
        $this->locked = 0;
        $this->catOrder = 0;
        $this->forums = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getCatTitle();
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setCatTitle(string $catTitle): self
    {
        $this->catTitle = $catTitle;

        return $this;
    }

    public function getCatTitle(): string
    {
        return $this->catTitle;
    }

    public function setCatComment(string $catComment): self
    {
        $this->catComment = $catComment;

        return $this;
    }

    public function getCatComment(): ?string
    {
        return $this->catComment;
    }

    public function setCatOrder(int $catOrder): self
    {
        $this->catOrder = $catOrder;

        return $this;
    }

    public function getCatOrder(): int
    {
        return $this->catOrder;
    }

    public function setLocked(int $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLocked(): int
    {
        return $this->locked;
    }

    /**
     * @return ArrayCollection<int, CForum>
     */
    public function getForums(): ArrayCollection
    {
        return $this->forums;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getCatTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setCatTitle($name);
    }
}
