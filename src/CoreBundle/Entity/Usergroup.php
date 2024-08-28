<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\State\GroupMembersStateProvider;
use Chamilo\CoreBundle\State\UsergroupPostStateProcessor;
use Chamilo\CoreBundle\State\UsergroupStateProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Classes and social groups.
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/usergroup/{id}',
            normalizationContext: ['groups' => ['usergroup:read']],
            security: "is_granted('ROLE_USER')",
            name: 'get_usergroup'
        ),
        new Put(security: "is_granted('EDIT', object)"),
        new Delete(security: "is_granted('DELETE', object)"),
        new GetCollection(
            uriTemplate: '/usergroup/list/my',
            normalizationContext: ['groups' => ['usergroup:read']],
            security: "is_granted('ROLE_USER')",
            name: 'get_my_usergroups',
            provider: UsergroupStateProvider::class
        ),
        new GetCollection(
            uriTemplate: '/usergroup/list/newest',
            normalizationContext: ['groups' => ['usergroup:read']],
            security: "is_granted('ROLE_USER')",
            name: 'get_newest_usergroups',
            provider: UsergroupStateProvider::class
        ),
        new GetCollection(
            uriTemplate: '/usergroup/list/popular',
            normalizationContext: ['groups' => ['usergroup:read']],
            security: "is_granted('ROLE_USER')",
            name: 'get_popular_usergroups',
            provider: UsergroupStateProvider::class
        ),
        new GetCollection(
            uriTemplate: '/usergroups/search',
            normalizationContext: ['groups' => ['usergroup:read']],
            security: "is_granted('ROLE_USER')",
            name: 'search_usergroups',
            provider: UsergroupStateProvider::class
        ),
        new GetCollection(
            uriTemplate: '/usergroups/{id}/members',
            normalizationContext: ['groups' => ['usergroup:read']],
            security: "is_granted('ROLE_USER')",
            name: 'get_group_members',
            provider: GroupMembersStateProvider::class
        ),
        new Post(
            securityPostDenormalize: "is_granted('CREATE', object)",
            processor: UsergroupPostStateProcessor::class
        ),
    ],
    normalizationContext: [
        'groups' => ['usergroup:read'],
    ],
    denormalizationContext: [
        'groups' => ['usergroup:write'],
    ],
    security: "is_granted('ROLE_USER')",
)]
#[ORM\Table(name: 'usergroup')]
#[ORM\Entity(repositoryClass: UsergroupRepository::class)]
class Usergroup extends AbstractResource implements ResourceInterface, ResourceIllustrationInterface, ResourceWithAccessUrlInterface, Stringable
{
    use TimestampableEntity;
    public const SOCIAL_CLASS = 1;
    public const NORMAL_CLASS = 0;
    // Definition of constants for user permissions
    public const GROUP_USER_PERMISSION_ADMIN = 1; // The admin of a group
    public const GROUP_USER_PERMISSION_READER = 2; // A normal user
    public const GROUP_USER_PERMISSION_PENDING_INVITATION = 3; // When an admin/moderator invites a user
    public const GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER = 4; // A user requests to join a group
    public const GROUP_USER_PERMISSION_MODERATOR = 5; // A moderator of the group
    public const GROUP_USER_PERMISSION_ANONYMOUS = 6; // An anonymous user, not part of the group
    public const GROUP_USER_PERMISSION_HRM = 7; // A human resource manager

    public const GROUP_PERMISSION_OPEN = 1;
    public const GROUP_PERMISSION_CLOSED = 2;

    #[Groups(['usergroup:read'])]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[Assert\NotBlank]
    #[Groups(['usergroup:read', 'usergroup:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title;
    #[Groups(['usergroup:read', 'usergroup:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;
    #[Assert\NotBlank]
    #[Groups(['usergroup:read', 'usergroup:write'])]
    #[ORM\Column(name: 'group_type', type: 'integer', nullable: false)]
    protected int $groupType;
    #[ORM\Column(name: 'picture', type: 'string', length: 255, nullable: true)]
    protected ?string $picture = null;
    #[Groups(['usergroup:read', 'usergroup:write'])]
    #[ORM\Column(name: 'url', type: 'string', length: 255, nullable: true)]
    protected ?string $url = null;
    #[Assert\NotBlank]
    #[Groups(['usergroup:read', 'usergroup:write'])]
    #[ORM\Column(name: 'visibility', type: 'string', length: 255, nullable: false)]
    protected string $visibility;
    #[ORM\Column(name: 'author_id', type: 'integer', nullable: true)]
    protected ?int $authorId = null;
    #[Assert\NotBlank]
    #[Groups(['usergroup:read', 'usergroup:write'])]
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

    #[Groups(['usergroup:read'])]
    private ?int $memberCount = null;

    #[Groups(['usergroup:read', 'usergroup:write'])]
    private ?string $pictureUrl = '';

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
        return $this->getTitle();
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
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
    public function getTitle(): string
    {
        return $this->title;
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

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    public function getPictureUrl(): ?string
    {
        return $this->pictureUrl;
    }

    public function setPictureUrl(?string $pictureUrl): self
    {
        $this->pictureUrl = $pictureUrl;

        return $this;
    }

    public function getMemberCount(): ?int
    {
        return $this->memberCount;
    }

    public function setMemberCount(int $memberCount): self
    {
        $this->memberCount = $memberCount;

        return $this;
    }

    public function getDefaultIllustration(int $size): string
    {
        $size = empty($size) ? 32 : $size;

        return \sprintf('/img/icons/%s/group_na.png', $size);
    }
    public function getResourceIdentifier(): int
    {
        return $this->getId();
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
