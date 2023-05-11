<?php

declare (strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
#[ApiResource(operations: [new Get(security: 'is_granted(\'VIEW\', object)'), new Put(security: 'is_granted(\'EDIT\', object)'), new Delete(security: 'is_granted(\'DELETE\', object)'), new GetCollection(security: 'is_granted(\'ROLE_USER\')'), new Post(securityPostDenormalize: 'is_granted(\'CREATE\', object)')], security: 'is_granted(\'ROLE_USER\')', denormalizationContext: ['groups' => ['message:write']], normalizationContext: ['groups' => ['message:read']])]
#[ORM\Table(name: 'message')]
#[ORM\Index(name: 'idx_message_user_sender', columns: ['user_sender_id'])]
#[ORM\Index(name: 'idx_message_group', columns: ['group_id'])]
#[ORM\Index(name: 'idx_message_type', columns: ['msg_type'])]
#[ORM\Entity(repositoryClass: \Chamilo\CoreBundle\Repository\MessageRepository::class)]
#[ORM\EntityListeners([\Chamilo\CoreBundle\Entity\Listener\MessageListener::class])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['title', 'sendDate'])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['msgType' => 'exact', 'status' => 'exact', 'sender' => 'exact', 'receivers.receiver' => 'exact', 'receivers.tags.tag' => 'exact', 'parent' => 'exact'])]
class Message
{
    public const MESSAGE_TYPE_INBOX = 1;
    public const MESSAGE_TYPE_GROUP = 5;
    public const MESSAGE_TYPE_INVITATION = 6;
    public const MESSAGE_TYPE_CONVERSATION = 7;
    // status
    public const MESSAGE_STATUS_DELETED = 3;
    public const MESSAGE_STATUS_DRAFT = 4;
    public const MESSAGE_STATUS_INVITATION_PENDING = 5;
    public const MESSAGE_STATUS_INVITATION_ACCEPTED = 6;
    public const MESSAGE_STATUS_INVITATION_DENIED = 7;
    #[ApiProperty(identifier: true)]
    #[Groups(['message:read'])]
    #[ORM\Column(name: 'id', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class, inversedBy: 'sentMessages')]
    #[ORM\JoinColumn(name: 'user_sender_id', referencedColumnName: 'id', nullable: false)]
    protected User $sender;
    /**
     * @var Collection|MessageRelUser[]
     */
    #[Assert\Valid]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\MessageRelUser::class, mappedBy: 'message', cascade: ['persist', 'remove'])]
    protected array|null|Collection $receivers;
    /**
     * @var Collection|MessageRelUser[]
     */
    #[Groups(['message:read', 'message:write'])]
    protected array|null|Collection $receiversTo;
    /**
     * @var Collection|MessageRelUser[]
     */
    #[Groups(['message:read', 'message:write'])]
    protected array|null|Collection $receiversCc;
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'msg_type', type: 'smallint', nullable: false)]
    protected int $msgType;
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'status', type: 'smallint', nullable: false)]
    protected int $status;
    #[Groups(['message:read'])]
    #[ORM\Column(name: 'send_date', type: 'datetime', nullable: false)]
    protected DateTime $sendDate;
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'content', type: 'text', nullable: false)]
    protected string $content;
    #[Groups(['message:read', 'message:write'])]
    protected ?MessageRelUser $firstReceiver = null;
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Usergroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Usergroup $group = null;
    /**
     * @var Collection|Message[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\Message::class, mappedBy: 'parent')]
    protected Collection $children;
    #[Groups(['message:write'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Message::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    protected ?Message $parent = null;
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'update_date', type: 'datetime', nullable: true)]
    protected ?DateTime $updateDate;
    #[ORM\Column(name: 'votes', type: 'integer', nullable: true)]
    protected ?int $votes;
    /**
     * @var Collection|MessageAttachment[]
     */
    #[Groups(['message:read'])]
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\MessageAttachment::class, mappedBy: 'message', cascade: ['remove', 'persist'])]
    protected Collection $attachments;
    public function __construct()
    {
        $this->sendDate = new DateTime('now');
        $this->updateDate = $this->sendDate;
        $this->msgType = self::MESSAGE_TYPE_INBOX;
        $this->content = '';
        $this->attachments = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->receivers = new ArrayCollection();
        $this->receiversCc = new ArrayCollection();
        $this->receiversTo = new ArrayCollection();
        $this->votes = 0;
        $this->status = 0;
    }
    /**
     * @return null|Collection|MessageRelUser[]
     */
    public function getReceivers() : null|\Doctrine\Common\Collections\Collection|array
    {
        return $this->receivers;
    }
    /**
     * @return MessageRelUser[]
     */
    public function getReceiversTo()
    {
        /*return $this->getReceivers()->filter(function (MessageRelUser $messageRelUser) {
              return MessageRelUser::TYPE_TO === $messageRelUser->getReceiverType();
          });*/
        $list = [];
        foreach ($this->receivers as $receiver) {
            if (MessageRelUser::TYPE_TO === $receiver->getReceiverType()) {
                $list[] = $receiver;
            }
        }
        return $list;
    }
    /**
     * @return MessageRelUser[]
     */
    public function getReceiversCc()
    {
        $list = [];
        foreach ($this->receivers as $receiver) {
            if (MessageRelUser::TYPE_CC === $receiver->getReceiverType()) {
                $list[] = $receiver;
            }
        }
        /*
        For some reason this doesn't work, api platform returns an obj instead a collection.
        $result = $this->receivers->filter(function (MessageRelUser $messageRelUser) {
            error_log((string)$messageRelUser->getId());
            return MessageRelUser::TYPE_CC === $messageRelUser->getReceiverType();
        });
        */
        return $list;
    }
    public function getFirstReceiver() : ?MessageRelUser
    {
        if ($this->receivers->count() > 0) {
            return $this->receivers->first();
        }
        return null;
    }
    public function hasReceiver(User $receiver)
    {
        if ($this->receivers->count()) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('receiver', $receiver))->andWhere(Criteria::expr()->eq('message', $this));
            return $this->receivers->matching($criteria)->count() > 0;
        }
        return false;
    }
    public function addReceiver(User $receiver, int $receiverType = MessageRelUser::TYPE_TO) : self
    {
        $messageRelUser = (new MessageRelUser())->setReceiver($receiver)->setReceiverType($receiverType)->setMessage($this);
        if (!$this->receivers->contains($messageRelUser)) {
            $this->receivers->add($messageRelUser);
        }
        return $this;
    }
    public function setReceivers(\Doctrine\Common\Collections\Collection|\Chamilo\CoreBundle\Entity\MessageRelUser $receivers) : self
    {
        /** @var MessageRelUser $receiver */
        foreach ($receivers as $receiver) {
            $receiver->setMessage($this);
        }
        $this->receivers = $receivers;
        return $this;
    }
    public function setSender(User $sender) : self
    {
        $this->sender = $sender;
        return $this;
    }
    public function getSender() : User
    {
        return $this->sender;
    }
    public function setMsgType(int $msgType) : self
    {
        $this->msgType = $msgType;
        return $this;
    }
    public function getMsgType() : int
    {
        return $this->msgType;
    }
    public function setSendDate(DateTime $sendDate) : self
    {
        $this->sendDate = $sendDate;
        return $this;
    }
    /**
     * Get sendDate.
     *
     * @return DateTime
     */
    public function getSendDate()
    {
        return $this->sendDate;
    }
    public function setTitle(string $title) : self
    {
        $this->title = $title;
        return $this;
    }
    public function getTitle() : string
    {
        return $this->title;
    }
    public function setContent(string $content) : self
    {
        $this->content = $content;
        return $this;
    }
    public function getContent() : string
    {
        return $this->content;
    }
    public function setUpdateDate(DateTime $updateDate) : self
    {
        $this->updateDate = $updateDate;
        return $this;
    }
    /**
     * Get updateDate.
     *
     * @return DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
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
    public function setVotes(int $votes) : self
    {
        $this->votes = $votes;
        return $this;
    }
    public function getVotes() : int
    {
        return $this->votes;
    }
    /**
     * Get attachments.
     *
     * @return Collection|MessageAttachment[]
     */
    public function getAttachments() : \Doctrine\Common\Collections\Collection|array
    {
        return $this->attachments;
    }
    public function addAttachment(MessageAttachment $attachment) : self
    {
        $this->attachments->add($attachment);
        $attachment->setMessage($this);
        return $this;
    }
    public function getParent() : ?self
    {
        return $this->parent;
    }
    /**
     * @return Collection|Message[]
     */
    public function getChildren() : \Doctrine\Common\Collections\Collection|array
    {
        return $this->children;
    }
    public function addChild(self $child) : self
    {
        $this->children[] = $child;
        $child->setParent($this);
        return $this;
    }
    public function setParent(self $parent = null) : self
    {
        $this->parent = $parent;
        return $this;
    }
    public function getGroup() : ?Usergroup
    {
        return $this->group;
    }
    public function setGroup(?Usergroup $group) : self
    {
        //        $this->msgType = self::MESSAGE_TYPE_GROUP;
        $this->group = $group;
        return $this;
    }
    public function getStatus() : int
    {
        return $this->status;
    }
    public function setStatus(int $status) : self
    {
        $this->status = $status;
        return $this;
    }
}
