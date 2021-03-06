<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_ADMIN')"},
 *     normalizationContext={"groups"={"usergroup:read"}}
 * )
 *
 * @ORM\Table(name="usergroup")
 * @ORM\Entity
 */
class Usergroup extends AbstractResource implements ResourceInterface, ResourceIllustrationInterface, ResourceToRootInterface
{
    use TimestampableEntity;

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="group_type", type="integer", nullable=false)
     */
    protected int $groupType;

    /**
     * @ORM\Column(name="picture", type="string", length=255, nullable=true)
     */
    protected ?string $picture = null;

    /**
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    protected ?string $url = null;

    /**
     * @ORM\Column(name="visibility", type="string", length=255, nullable=false)
     */
    protected string $visibility;

    /**
     * @ORM\Column(name="author_id", type="integer", nullable=true)
     */
    protected ?string $authorId = null;

    /**
     * @ORM\Column(name="allow_members_leave_group", type="integer")
     */
    protected int $allowMembersToLeaveGroup;

    /**
     * @var Collection|UsergroupRelUser[]
     * @ORM\OneToMany(targetEntity="UsergroupRelUser", mappedBy="usergroup", cascade={"persist"}, orphanRemoval=true)
     */
    protected Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function setUsers($users): void
    {
        $this->users = new ArrayCollection();

        foreach ($users as $user) {
            $this->addUsers($user);
        }
    }

    public function addUsers(UsergroupRelUser $user): void
    {
        $user->setUsergroup($this);
        $this->users[] = $user;
    }

    /**
     * Remove $user.
     */
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

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

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
     * @return int
     */
    public function getGroupType()
    {
        return $this->groupType;
    }

    /**
     * @return Usergroup
     */
    public function setGroupType(int $groupType)
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
