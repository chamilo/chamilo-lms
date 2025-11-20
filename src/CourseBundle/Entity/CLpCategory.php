<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'LearningPathCategories',
    operations: [
        new GetCollection(
            openapi: new Operation(
                summary: 'List LP categories by course (resourceNode.parent) or sid',
                parameters: [
                    new Parameter(
                        name: 'resourceNode.parent',
                        in: 'query',
                        description: 'Parent ResourceNode (course node id)',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'sid',
                        in: 'query',
                        description: 'Session id (SidFilter si aplica)',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
        ),
        new Get(security: "is_granted('ROLE_USER')"),
    ],
    normalizationContext: [
        'groups' => ['lp_category:read', 'resource_node:read', 'resource_link:read'],
        'enable_max_depth' => true,
    ],
    paginationEnabled: false,
)]
#[ApiFilter(SearchFilter::class, properties: [
    'resourceNode.parent' => 'exact',
    'title' => 'partial',
])]
#[ORM\Table(name: 'c_lp_category')]
#[ORM\Entity(repositoryClass: CLpCategoryRepository::class)]
class CLpCategory extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['lp_category:read', 'lp:read'])]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text')]
    #[Groups(['lp_category:read', 'lp:read'])]
    protected string $title;

    /**
     * @var Collection<int, CLpCategoryRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CLpCategoryRelUser::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $users;

    /**
     * @var Collection<int, CLp>
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CLp::class, cascade: ['detach', 'persist'])]
    protected Collection $lps;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->lps = new ArrayCollection();
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
     * Get category name.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return Collection<int, CLp>
     */
    public function getLps(): Collection
    {
        return $this->lps;
    }

    /**
     * @return Collection<int, CLpCategoryRelUser>
     */
    public function getUsers(): Collection
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

    public function addUser(CLpCategoryRelUser $categoryUser): void
    {
        $categoryUser->setCategory($this);

        if (!$this->hasUser($categoryUser)) {
            $this->users->add($categoryUser);
        }
    }

    public function hasUser(CLpCategoryRelUser $categoryUser): bool
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
            $categoryUser = new CLpCategoryRelUser();
            $categoryUser->setCategory($this);
            $categoryUser->setUser($user);

            return $this->hasUser($categoryUser);
        }

        return false;
    }

    public function removeUsers(CLpCategoryRelUser $user): self
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
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
