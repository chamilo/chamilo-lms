<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\AiTutorConversationRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiTutorConversationRepository::class)]
#[ORM\Table(name: 'ai_tutor_conversation')]
#[ORM\UniqueConstraint(name: 'uniq_ai_tutor_conv_user_course_provider', columns: ['user_id', 'course_id', 'ai_provider'])]
#[ORM\Index(columns: ['user_id', 'course_id'], name: 'idx_ai_tutor_conv_user_course')]
#[ORM\Index(columns: ['course_id'], name: 'idx_ai_tutor_conv_course')]
#[ORM\Index(columns: ['last_message_at'], name: 'idx_ai_tutor_conv_last_message')]
#[ORM\HasLifecycleCallbacks]
class AiTutorConversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Session $session = null;

    #[ORM\Column(name: 'ai_provider', type: 'string', length: 50, nullable: false)]
    protected string $aiProvider;

    #[ORM\Column(name: 'provider_conversation_id', type: 'string', length: 255, nullable: true)]
    protected ?string $providerConversationId = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    protected DateTime $updatedAt;

    #[ORM\Column(name: 'last_message_at', type: 'datetime', nullable: true)]
    protected ?DateTime $lastMessageAt = null;

    /**
     * @var Collection<int, AiTutorMessage>
     */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: AiTutorMessage::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    protected Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $now = new DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getAiProvider(): string
    {
        return $this->aiProvider;
    }

    public function setAiProvider(string $aiProvider): self
    {
        $this->aiProvider = $aiProvider;

        return $this;
    }

    public function getProviderConversationId(): ?string
    {
        return $this->providerConversationId;
    }

    public function setProviderConversationId(?string $providerConversationId): self
    {
        $this->providerConversationId = $providerConversationId;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getLastMessageAt(): ?DateTime
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(?DateTime $lastMessageAt): self
    {
        $this->lastMessageAt = $lastMessageAt;

        return $this;
    }

    public function touchLastMessageAt(): self
    {
        $now = new DateTime();
        $this->lastMessageAt = $now;
        $this->updatedAt = $now;

        return $this;
    }

    /**
     * @return Collection<int, AiTutorMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(AiTutorMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
            $this->touchLastMessageAt();
        }

        return $this;
    }
}
