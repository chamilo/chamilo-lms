<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Learning paths categories.
 */
#[ORM\Table(name: 'c_lp_category')]
#[ORM\Entity(repositoryClass: \Gedmo\Sortable\Entity\Repository\SortableRepository::class)]
class CLpCategory extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'name', type: 'text')]
    protected string $name;

    #[Gedmo\SortablePosition]
    #[ORM\Column(name: 'position', type: 'integer')]
    protected int $position;

    /**
     * @var Collection|CLpCategoryUser[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CourseBundle\Entity\CLpCategoryUser::class, mappedBy: 'category', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $users;

    /**
     * @var Collection|CLp[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CourseBundle\Entity\CLp::class, mappedBy: 'category', cascade: ['detach'])]
    protected Collection $lps;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->lps = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get category name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return Collection|CLp[]
     */
    public function getLps(): Collection|array
    {
        return $this->lps;
    }

    /**
     * @return Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    public function setUsers(Collection $users): void
    {
        $this->users = new ArrayCollection();
        foreach ($users as $user) {
            $this->addUser($user);
        }
    }

    public function addUser(CLpCategoryUser $categoryUser): void
    {
        $categoryUser->setCategory($this);

        if (!$this->hasUser($categoryUser)) {
            $this->users->add($categoryUser);
        }
    }

    public function hasUser(CLpCategoryUser $categoryUser): bool
    {
        if (0 !== $this->getUsers()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('user', $categoryUser->getUser())
            )->andWhere(
                Criteria::expr()->eq('category', $categoryUser->getCategory())
            );

            $relation = $this->getUsers()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    public function hasUserAdded(User $user): bool
    {
        if (0 !== $this->getUsers()->count()) {
            $categoryUser = new CLpCategoryUser();
            $categoryUser->setCategory($this);
            $categoryUser->setUser($user);

            return $this->hasUser($categoryUser);
        }

        return false;
    }

    public function removeUsers(CLpCategoryUser $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * Resource identifier.
     */
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
}
