<?php

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
    #[Groups(['social_post:read'])]
    protected User $sender;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="receivedSocialPosts")
     */
    #[Groups(['social_post:read'])]
    protected User $userReceiver;

    /**
     * @ORM\Column(type="text")
     */
    #[Groups(['social_post:read'])]
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
    #[Groups(['social_post:read'])]
    protected DateTime $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SocialPostFeedback", mappedBy="socialPost")
     */
    protected Collection $feedbacks;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Usergroup")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected ?Usergroup $groupReceiver = null;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SocialPost", mappedBy="parent")
     */
    protected Collection $children;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SocialPost", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?SocialPost $parent = null;

    #[Groups(['social_post:read'])]
    protected int $countFeedbackLikes = 0;

    #[Groups(['social_post:read'])]
    protected int $countFeedbackDislikes = 0;

    public function __construct()
    {
        $this->sendDate = new DateTime();
        $this->updatedAt = $this->sendDate;
        $this->status = self::STATUS_SENT;
        $this->feedbacks = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): SocialPost
    {
        $this->id = $id;

        return $this;
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function setSender(User $sender): SocialPost
    {
        $this->sender = $sender;

        return $this;
    }

    public function getUserReceiver(): User
    {
        return $this->userReceiver;
    }

    public function setUserReceiver(User $userReceiver): SocialPost
    {
        $this->userReceiver = $userReceiver;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): SocialPost
    {
        $this->status = $status;

        return $this;
    }

    public function getSendDate(): DateTime
    {
        return $this->sendDate;
    }

    public function setSendDate(DateTime $sendDate): SocialPost
    {
        $this->sendDate = $sendDate;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): SocialPost
    {
        $this->content = $content;

        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): SocialPost
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFeedbacks(): Collection
    {
        return $this->feedbacks;
    }

    public function setFeedbacks(Collection $feedbacks): SocialPost
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
}
