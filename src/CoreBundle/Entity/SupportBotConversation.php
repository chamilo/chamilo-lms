<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'support_bot_conversation')]
#[ORM\Index(columns: ['tree_id', 'user_id'], name: 'idx_support_bot_conv_tree_user')]
#[ORM\Index(columns: ['access_url_id'], name: 'idx_support_bot_conv_access_url')]
#[ORM\Index(columns: ['outcome', 'started_at'], name: 'idx_support_bot_conv_outcome_started')]
class SupportBotConversation
{
    public const string OUTCOME_ABANDONED = 'abandoned';
    public const string OUTCOME_ESCALATED = 'escalated';
    public const string OUTCOME_IN_PROGRESS = 'in_progress';
    public const string OUTCOME_RESOLVED = 'resolved';

    private const array VALID_OUTCOMES = [
        self::OUTCOME_IN_PROGRESS,
        self::OUTCOME_RESOLVED,
        self::OUTCOME_ESCALATED,
        self::OUTCOME_ABANDONED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SupportBotTree::class)]
    #[ORM\JoinColumn(name: 'tree_id', referencedColumnName: 'id', nullable: false)]
    protected SupportBotTree $tree;

    #[ORM\Column(name: 'tree_version', type: 'integer')]
    protected int $treeVersion = 1;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?AccessUrl $accessUrl = null;

    #[ORM\ManyToOne(targetEntity: SupportBotNode::class)]
    #[ORM\JoinColumn(name: 'current_node_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?SupportBotNode $currentNode = null;

    #[ORM\Column(name: 'outcome', type: 'string', length: 20)]
    protected string $outcome = self::OUTCOME_IN_PROGRESS;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(name: 'resulting_ticket_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Ticket $resultingTicket = null;

    #[ORM\Column(name: 'started_at', type: 'datetime')]
    protected DateTime $startedAt;

    #[ORM\Column(name: 'ended_at', type: 'datetime', nullable: true)]
    protected ?DateTime $endedAt = null;

    /**
     * @var Collection<int, SupportBotStep>
     */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: SupportBotStep::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC', 'id' => 'ASC'])]
    protected Collection $steps;

    public function __construct()
    {
        $this->steps = new ArrayCollection();
        $this->startedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTree(): SupportBotTree
    {
        return $this->tree;
    }

    public function setTree(SupportBotTree $tree): self
    {
        $this->tree = $tree;

        return $this;
    }

    public function getTreeVersion(): int
    {
        return $this->treeVersion;
    }

    public function setTreeVersion(int $treeVersion): self
    {
        $this->treeVersion = max(1, $treeVersion);

        return $this;
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

    public function getAccessUrl(): ?AccessUrl
    {
        return $this->accessUrl;
    }

    public function setAccessUrl(?AccessUrl $accessUrl): self
    {
        $this->accessUrl = $accessUrl;

        return $this;
    }

    public function getCurrentNode(): ?SupportBotNode
    {
        return $this->currentNode;
    }

    public function setCurrentNode(?SupportBotNode $currentNode): self
    {
        $this->currentNode = $currentNode;

        return $this;
    }

    public function getOutcome(): string
    {
        return $this->outcome;
    }

    public function setOutcome(string $outcome): self
    {
        if (!\in_array($outcome, self::VALID_OUTCOMES, true)) {
            throw new InvalidArgumentException('Invalid support bot conversation outcome.');
        }

        $this->outcome = $outcome;

        return $this;
    }

    public function getResultingTicket(): ?Ticket
    {
        return $this->resultingTicket;
    }

    public function setResultingTicket(?Ticket $resultingTicket): self
    {
        $this->resultingTicket = $resultingTicket;

        return $this;
    }

    public function getStartedAt(): DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(DateTime $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?DateTime
    {
        return $this->endedAt;
    }

    public function setEndedAt(?DateTime $endedAt): self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    /**
     * @return Collection<int, SupportBotStep>
     */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function addStep(SupportBotStep $step): self
    {
        if (!$this->steps->contains($step)) {
            $this->steps->add($step);
            $step->setConversation($this);
        }

        return $this;
    }

    public function getLastStep(): ?SupportBotStep
    {
        $step = $this->steps->last();

        return false === $step ? null : $step;
    }
}
