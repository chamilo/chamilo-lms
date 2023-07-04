<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Controller\Api\DislikeSocialPostController;
use Chamilo\CoreBundle\Controller\Api\LikeSocialPostController;
use Chamilo\CoreBundle\Filter\SocialWallFilter;
use Chamilo\CoreBundle\Repository\SocialPostRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'social_post')]
#[ORM\Index(columns: ['sender_id'], name: 'idx_social_post_sender')]
#[ORM\Index(columns: ['user_receiver_id'], name: 'idx_social_post_user')]
#[ORM\Index(columns: ['group_receiver_id'], name: 'idx_social_post_group')]
#[ORM\Index(columns: ['type'], name: 'idx_social_post_type')]
#[ORM\Entity(repositoryClass: SocialPostRepository::class)]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('VIEW', object)"),
        new Put(security: "is_granted('EDIT', object)"),
        new Delete(security: "is_granted('DELETE', object)"),
        new Post(securityPostDenormalize: "is_granted('CREATE', object)"),
        new Post(
            uriTemplate: '/social_posts/{id}/like',
            controller: LikeSocialPostController::class,
            normalizationContext: ['groups' => ['social_post_feedback']],
            denormalizationContext: ['groups' => []],
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            uriTemplate: '/social_posts/{id}/dislike',
            controller: DislikeSocialPostController::class,
            normalizationContext: ['groups' => ['social_post_feedback']],
            denormalizationContext: ['groups' => []],
            security: "is_granted('ROLE_USER')"
        ),
        new GetCollection(security: "is_granted('ROLE_USER')"),
    ],
    normalizationContext: ['groups' => ['social_post:read']],
    denormalizationContext: ['groups' => ['social_post:write']],
    security: "is_granted('ROLE_USER')"
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['parent' => 'exact'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['sendDate'])]
class SocialPost
{
    public const TYPE_WALL_POST = 1;
    public const TYPE_WALL_COMMENT = 2;
    public const TYPE_GROUP_MESSAGE = 3;
    public const TYPE_PROMOTED_MESSAGE = 4;
    public const STATUS_SENT = 1;
    public const STATUS_DELETED = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'bigint')]
    protected ?int $id = null;

    #[Groups(['social_post:read', 'social_post:write'])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sentSocialPosts')]
    #[ORM\JoinColumn(nullable: false)]
    protected User $sender;

    #[Groups(['social_post:read', 'social_post:write'])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'receivedSocialPosts')]
    #[ORM\JoinColumn(nullable: true)]
    protected ?User $userReceiver;

    #[ORM\Column(name: 'subject', type: 'text', nullable: true)]
    protected ?string $subject = null;

    #[Groups(['social_post:read', 'social_post:write'])]
    #[ORM\Column(type: 'text')]
    protected string $content;

    #[Groups(['social_post:write', 'social_post:read'])]
    #[Assert\Choice([
        self::TYPE_WALL_POST,
        self::TYPE_WALL_COMMENT,
        self::TYPE_GROUP_MESSAGE,
        self::TYPE_PROMOTED_MESSAGE,
    ], message: 'Choose a valid type.')]
    #[ORM\Column(type: 'smallint')]
    protected int $type;

    #[Assert\Choice([self::STATUS_SENT, self::STATUS_DELETED], message: 'Choose a status.')]
    #[ORM\Column(type: 'smallint')]
    protected int $status;

    #[Groups(['social_post:read'])]
    #[ORM\Column(type: 'datetime')]
    protected DateTime $sendDate;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime')]
    protected DateTime $updatedAt;

    #[ORM\OneToMany(targetEntity: SocialPostFeedback::class, mappedBy: 'socialPost')]
    protected Collection $feedbacks;

    #[Groups(['social_post:read', 'social_post:write'])]
    #[ORM\ManyToOne(targetEntity: Usergroup::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected ?Usergroup $groupReceiver = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    protected Collection $children;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?SocialPost $parent = null;

    #[Groups(['social_post:read', 'social_post_feedback'])]
    protected int $countFeedbackLikes = 0;

    #[Groups(['social_post:read', 'social_post_feedback'])]
    protected int $countFeedbackDislikes = 0;

    #[ApiFilter(filterClass: SocialWallFilter::class)]
    protected User $wallOwner;

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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

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
        $criteria->where(Criteria::expr()->eq('liked', true));

        return $this->feedbacks->matching($criteria)->count();
    }

    public function getCountFeedbackDislikes(): int
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('disliked', true));

        return $this->feedbacks->matching($criteria)->count();
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
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
