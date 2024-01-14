<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Classes and social groups.
 */
#[ApiResource(security: 'is_granted(\'ROLE_ADMIN\')', normalizationContext: ['groups' => ['usergroup:read']])]
#[ORM\Table(name: 'usergroup')]
#[ORM\Entity(repositoryClass: UsergroupRepository::class)]
class Usergroup extends AbstractResource implements ResourceInterface, ResourceIllustrationInterface, ResourceWithAccessUrlInterface, Stringable
{
    use TimestampableEntity;
    public const SOCIAL_CLASS = 1;
    public const NORMAL_CLASS = 0;
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[Assert\NotBlank]
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    protected string $name;
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;
    #[Assert\NotBlank]
    #[ORM\Column(name: 'group_type', type: 'integer', nullable: false)]
    protected int $groupType;
    #[ORM\Column(name: 'picture', type: 'string', length: 255, nullable: true)]
    protected ?string $picture = null;
    #[ORM\Column(name: 'url', type: 'string', length: 255, nullable: true)]
    protected ?string $url = null;
    #[Assert\NotBlank]
    #[ORM\Column(name: 'visibility', type: 'string', length: 255, nullable: false)]
    protected string $visibility;
    #[ORM\Column(name: 'author_id', type: 'integer', nullable: true)]
    protected ?int $authorId = null;
    #[Assert\NotBlank]
    #[ORM\Column(name: 'allow_members_leave_group', type: 'integer')]
    protected int $allowMembersToLeaveGroup;

    /**
     * @var Collection<int, UsergroupRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'usergroup', targetEntity: UsergroupRelUser::class, cascade: ['persist'])]
    protected Collection $users;

    /**
     * @var Collection<int, UsergroupRelCourse>
     */
    #[ORM\OneToMany(mappedBy: 'usergroup', targetEntity: UsergroupRelCourse::class, cascade: ['persist'])]
    protected Collection $courses;

    /**
     * @var Collection<int, UsergroupRelSession>
     */
    #[ORM\OneToMany(mappedBy: 'usergroup', targetEntity: UsergroupRelSession::class, cascade: ['persist'])]
    protected Collection $sessions;

    /**
     * @var Collection<int, UsergroupRelQuestion>
     */
    #[ORM\OneToMany(mappedBy: 'usergroup', targetEntity: UsergroupRelQuestion::class, cascade: ['persist'])]
    protected Collection $questions;

    /**
     * @var Collection<int, AccessUrlRelUserGroup>
     */
    #[ORM\OneToMany(mappedBy: 'userGroup', targetEntity: AccessUrlRelUserGroup::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $urls;
    public function __construct()
    {
        $this->groupType = self::NORMAL_CLASS;
        $this->visibility = GROUP_PERMISSION_OPEN;
        $this->allowMembersToLeaveGroup = 0;
        $this->users = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->questions = new ArrayCollection();
    }
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return Collection<int, UsergroupRelUser>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return Collection<int, AccessUrlRelUserGroup>
     */
    public function getUrls(): Collection
    {
        return $this->urls;
    }
    public function addAccessUrl(?AccessUrl $url): self
    {
        $urlRelUsergroup = new AccessUrlRelUserGroup();
        $urlRelUsergroup->setUserGroup($this);
        $urlRelUsergroup->setUrl($url);
        $this->addUrlRelUsergroup($urlRelUsergroup);

        return $this;
    }
    public function addUrlRelUsergroup(AccessUrlRelUserGroup $urlRelUsergroup): self
    {
        $urlRelUsergroup->setUserGroup($this);
        $this->urls[] = $urlRelUsergroup;

        return $this;
    }
    public function setUsers(Collection $users): void
    {
        $this->users = new ArrayCollection();
        foreach ($users as $user) {
            $this->addUsers($user);
        }
    }
    public function addUsers(UsergroupRelUser $user): self
    {
        $user->setUsergroup($this);
        $this->users[] = $user;

        return $this;
    }
    public function removeUsers(UsergroupRelUser $user): void
    {
        foreach ($this->users as $key => $value) {
            if ($value->getId() === $user->getId()) {
                unset($this->users[$key]);
            }
        }
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getName(): string
    {
        return $this->name;
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

    public function getGroupType(): int
    {
        return $this->groupType;
    }
    public function setGroupType(int $groupType): self
    {
        $this->groupType = $groupType;

        return $this;
    }
    public function getVisibility(): string
    {
        return $this->visibility;
    }
    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }
    public function getUrl(): ?string
    {
        return $this->url;
    }
    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }
    public function getAuthorId(): ?int
    {
        return $this->authorId;
    }
    public function setAuthorId(?int $authorId): self
    {
        $this->authorId = $authorId;

        return $this;
    }
    public function getAllowMembersToLeaveGroup(): int
    {
        return $this->allowMembersToLeaveGroup;
    }
    public function setAllowMembersToLeaveGroup(int $allowMembersToLeaveGroup): self
    {
        $this->allowMembersToLeaveGroup = $allowMembersToLeaveGroup;

        return $this;
    }

    /**
     * @return Collection<int, UsergroupRelCourse>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }
    public function setCourses(Collection $courses): self
    {
        $this->courses = $courses;

        return $this;
    }

    /**
     * @return Collection<int, UsergroupRelSession>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }
    public function setSessions(Collection $sessions): self
    {
        $this->sessions = $sessions;

        return $this;
    }

    /**
     * @return Collection<int, UsergroupRelQuestion>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }
    public function setQuestions(Collection $questions): self
    {
        $this->questions = $questions;

        return $this;
    }
    public function getPicture(): ?string
    {
        return $this->picture;
    }
    public function getDefaultIllustration(int $size): string
    {
        $size = empty($size) ? 32 : $size;

        return sprintf('/img/icons/%s/group_na.png', $size);
    }
    public function getResourceIdentifier(): int
    {
        return $this->getId();
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
