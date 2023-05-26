<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
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
#[ORM\Entity(repositoryClass: \Chamilo\CoreBundle\Repository\Node\UsergroupRepository::class)]
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
    protected ?string $authorId = null;
    #[Assert\NotBlank]
    #[ORM\Column(name: 'allow_members_leave_group', type: 'integer')]
    protected int $allowMembersToLeaveGroup;
    /**
     * @var Collection|UsergroupRelUser[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\UsergroupRelUser::class, mappedBy: 'usergroup', cascade: ['persist'])]
    protected Collection $users;
    /**
     * @var Collection|UsergroupRelCourse[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\UsergroupRelCourse::class, mappedBy: 'usergroup', cascade: ['persist'])]
    protected Collection $courses;
    /**
     * @var Collection|UsergroupRelSession[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\UsergroupRelSession::class, mappedBy: 'usergroup', cascade: ['persist'])]
    protected Collection $sessions;
    /**
     * @var Collection|UsergroupRelQuestion[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\UsergroupRelQuestion::class, mappedBy: 'usergroup', cascade: ['persist'])]
    protected Collection $questions;
    /**
     * @var AccessUrlRelUserGroup[]|Collection
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\AccessUrlRelUserGroup::class, mappedBy: 'userGroup', cascade: ['persist', 'remove'], orphanRemoval: true)]
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
     * @return UsergroupRelUser[]|Collection
     */
    public function getUsers(): array|Collection
    {
        return $this->users;
    }
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
     *
     * @return int
     */
    public function getId()
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
    /**
     * @return int
     */
    public function getGroupType()
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
    public function getAuthorId(): string
    {
        return $this->authorId;
    }
    public function setAuthorId(string $authorId): self
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
     * @return UsergroupRelCourse[]|Collection
     */
    public function getCourses(): array|Collection
    {
        return $this->courses;
    }
    public function setCourses(Collection $courses): self
    {
        $this->courses = $courses;

        return $this;
    }
    /**
     * @return UsergroupRelSession[]|Collection
     */
    public function getSessions(): array|Collection
    {
        return $this->sessions;
    }
    public function setSessions(Collection $sessions): self
    {
        $this->sessions = $sessions;

        return $this;
    }
    /**
     * @return UsergroupRelQuestion[]|Collection
     */
    public function getQuestions(): array|Collection
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
