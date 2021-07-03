<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Associations between users.
 *
 * @ORM\Table(name="user_rel_user", indexes={
 *     @ORM\Index(name="idx_user_rel_user__user", columns={"user_id"}),
 *     @ORM\Index(name="idx_user_rel_user__friend_user", columns={"friend_user_id"}),
 *     @ORM\Index(name="idx_user_rel_user__user_friend_user", columns={"user_id", "friend_user_id"})
 * })
 * @ORM\Entity
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            //'security' => "is_granted('ROLE_ADMIN')",
        ],
        'post' => [
            //'security' => "is_granted('ROLE_ADMIN') or object.user == user",
        ],
    ],
    itemOperations: [
        'get' => [
            //'security' => "is_granted('ROLE_ADMIN')",
        ],
        'put' => [
            //'security' => "is_granted('ROLE_ADMIN') or object.user == user",
        ],
        'delete' => [
            //'security' => "is_granted('ROLE_ADMIN') or object.user == user",
        ],
    ],
    attributes: [
        'security' => 'is_granted("ROLE_USER")',
    ],
    denormalizationContext: [
        'groups' => ['message_tag:write'],
    ],
    normalizationContext: [
        'groups' => ['message_tag:read'],
    ],
)]
class UserRelUser
{
    use UserTrait;
    use TimestampableEntity;

    public const USER_RELATION_TYPE_UNKNOWN = 1;
    public const USER_RELATION_TYPE_PARENT = 2;
    public const USER_RELATION_TYPE_FRIEND = 3;
    public const USER_RELATION_TYPE_GOODFRIEND = 4; // should be deprecated is useless
    public const USER_RELATION_TYPE_ENEMY = 5; // should be deprecated is useless
    public const USER_RELATION_TYPE_DELETED = 6;
    public const USER_RELATION_TYPE_RRHH = 7;
    public const USER_RELATION_TYPE_BOSS = 8;
    public const USER_RELATION_TYPE_HRM_REQUEST = 9;

    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="friends")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="friendsWithMe")
     * @ORM\JoinColumn(name="friend_user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected User $friend;

    /**
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    protected int $relationType;

    public function __construct()
    {
        $this->relationType = self::USER_RELATION_TYPE_FRIEND;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getFriend(): User
    {
        return $this->friend;
    }

    public function setFriend(User $friend): self
    {
        $this->friend = $friend;

        return $this;
    }

    public function setRelationType(int $relationType): self
    {
        $this->relationType = $relationType;

        return $this;
    }

    public function getRelationType(): ?int
    {
        return $this->relationType;
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
}
