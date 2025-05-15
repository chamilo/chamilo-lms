<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_forum_category')]
#[ORM\Entity(repositoryClass: CForumCategoryRepository::class)]
class CForumCategory extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'cat_comment', type: 'text', nullable: true)]
    protected ?string $catComment;

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
        $this->forums = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->title;
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
     * @return Collection<int, CForum>
     */
    public function getForums(): Collection
    {
        return $this->forums;
    }

    public function getResourceIdentifier(): int|Uuid
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
