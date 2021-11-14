<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Repository\SocialPostRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="social_post", indexes={
 *     @ORM\Index(name="idx_social_post_sender", columns={"sender_id"}),
 *     @ORM\Index(name="idx_social_post_user", columns={"user_receiver_id"}),
 *     @ORM\Index(name="idx_social_post_group", columns={"group_receiver_id"}),
 *     @ORM\Index(name="idx_social_post_type", columns={"type"})
 * })
 * @ORM\Entity(repositoryClass=SocialPostRepository::class)
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            'security' => "is_granted('ROLE_USER')",
        ],
        'post' => [
            'security_post_denormalize' => "is_granted('CREATE', object)",
        ],
    ],
    itemOperations: [
        'get' => [
            'security' => "is_granted('VIEW', object)",
        ],
        'put' => [
            'security' => "is_granted('EDIT', object)",
        ],
        'delete' => [
            'security' => "is_granted('DELETE', object)",
        ],
    ],
    attributes: [
        'security' => "is_granted('ROLE_USER')",
    ],
    denormalizationContext: [
        'groups' => ['social_post:write'],
    ],
    normalizationContext: [
        'groups' => ['social_post:read'],
    ],
)]
class SocialPost
{
    public const TYPE_WALL_POST = 1;
    public const TYPE_WALL_COMMENT = 2;
    public const TYPE_GROUP_MESSAGE = 3;
    public const TYPE_PROMOTED_MESSAGE = 4;

    public const STATUS_SENT = 1;
    public const STATUS_DELETED = 2;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="sentSocialPosts")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups(['social_post:read', 'social_post:write'])]
    protected User $sender;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="receivedSocialPosts")
     * @ORM\JoinColumn(nullable=true)
     */
    #[Groups(['social_post:read', 'social_post:write'])]
    protected ?User $userReceiver;

    /**
     * @ORM\Column(type="text")
     */
    #[Groups(['social_post:read', 'social_post:write'])]
    protected string $content;

    /**
     * @Assert\Choice({
     *      SocialPost::TYPE_WALL_POST,
     *      SocialPost::TYPE_WALL_COMMENT,
     *      SocialPost::TYPE_GROUP_MESSAGE,
     *      SocialPost::TYPE_PROMOTED_MESSAGE,
     *  },
     *  message="Choose a valid type."
     * )
     * @ORM\Column(type="smallint")
     */
    #[Groups(['social_post:write'])]
    protected int $type;

    /**
     * @Assert\Choice({
     *     SocialPost::STATUS_SENT,
     *     SocialPost::STATUS_DELETED,
     * }, message="Choose a status.")
     *
     * @ORM\Column(type="smallint")
     */
    protected int $status;

    /**
     * @ORM\Column(type="datetime")
     */
    #[Groups(['social_post:read'])]
    protected DateTime $sendDate;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    protected DateTime $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SocialPostFeedback", mappedBy="socialPost")
     */
    protected Collection $feedbacks;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Usergroup")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    #[Groups(['social_post:read', 'social_post:write'])]
    protected ?Usergroup $groupReceiver = null;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SocialPost", mappedBy="parent")
     */
    protected Collection $children;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SocialPost", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Groups(['social_post:write'])]
    protected ?SocialPost $parent;

    #[Groups(['social_post:read'])]
    protected int $countFeedbackLikes;

    #[Groups(['social_post:read'])]
    protected int $countFeedbackDislikes;

    public function __construct()
    {
        $this->userReceiver = null;
        $this->groupReceiver = null;
        $this->parent = null;
        $this->sendDate = new DateTime();
        $this->updatedAt = $this->sendDate;
        $this->status = self::STATUS_SENT;
        $this->feedbacks = new ArrayCollection();
        $this->type = self::TYPE_WALL_POST;
        $this->countFeedbackLikes = 0;
        $this->countFeedbackDislikes = 0;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function setSender(User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getUserReceiver(): ?User
    {
        return $this->userReceiver;
    }

    public function setUserReceiver(?User $userReceiver): self
    {
        $this->userReceiver = $userReceiver;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSendDate(): DateTime
    {
        return $this->sendDate;
    }

    public function setSendDate(DateTime $sendDate): self
    {
        $this->sendDate = $sendDate;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFeedbacks(): Collection
    {
        return $this->feedbacks;
    }

    public function setFeedbacks(Collection $feedbacks): self
    {
        $this->feedbacks = $feedbacks;

        return $this;
    }

    public function addFeedback(SocialPostFeedback $feedback): self
    {
        if (!$this->feedbacks->contains($feedback)) {
            $this->feedbacks[] = $feedback;
            $feedback->setSocialPost($this);
        }

        return $this;
    }

    public function getCountFeedbackLikes(): int
    {
        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()
                ->eq('liked', true)
        );

        return $this->feedbacks->matching($criteria)
            ->count()
        ;
    }

    public function getCountFeedbackDislikes(): int
    {
        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()
                ->eq('disliked', true)
        );

        return $this->feedbacks->matching($criteria)
            ->count()
        ;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(self $parent = null): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, SocialPost>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    public function getGroupReceiver(): ?Usergroup
    {
        return $this->groupReceiver;
    }

    public function setGroupReceiver(?Usergroup $groupReceiver): self
    {
        $this->groupReceiver = $groupReceiver;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }
}
