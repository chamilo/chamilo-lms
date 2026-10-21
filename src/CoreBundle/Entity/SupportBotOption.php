<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'support_bot_option')]
#[ORM\Index(columns: ['node_id'], name: 'idx_support_bot_option_node')]
class SupportBotOption
{
    public const string ACTION_END_CONVERSATION = 'end_conversation';
    public const string ACTION_ESCALATE_TICKET = 'escalate_ticket';
    public const string ACTION_GOTO_NODE = 'goto_node';
    public const string ACTION_OPEN_LINK = 'open_link';

    private const array VALID_ACTION_TYPES = [
        self::ACTION_GOTO_NODE,
        self::ACTION_OPEN_LINK,
        self::ACTION_ESCALATE_TICKET,
        self::ACTION_END_CONVERSATION,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SupportBotNode::class, inversedBy: 'options')]
    #[ORM\JoinColumn(name: 'node_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected SupportBotNode $node;

    #[ORM\Column(name: 'label', type: 'string', length: 255)]
    protected string $label = '';

    #[ORM\Column(name: 'sort_order', type: 'integer')]
    protected int $sortOrder = 0;

    #[ORM\Column(name: 'action_type', type: 'string', length: 32)]
    protected string $actionType = self::ACTION_GOTO_NODE;

    #[ORM\ManyToOne(targetEntity: SupportBotNode::class)]
    #[ORM\JoinColumn(name: 'target_node_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?SupportBotNode $targetNode = null;

    #[ORM\Column(name: 'target_url', type: 'string', length: 500, nullable: true)]
    protected ?string $targetUrl = null;

    #[ORM\ManyToOne(targetEntity: TicketCategory::class)]
    #[ORM\JoinColumn(name: 'ticket_category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?TicketCategory $ticketCategory = null;

    #[ORM\Column(name: 'closing_message', type: 'text', nullable: true)]
    protected ?string $closingMessage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNode(): SupportBotNode
    {
        return $this->node;
    }

    public function setNode(SupportBotNode $node): self
    {
        $this->node = $node;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): self
    {
        if (!\in_array($actionType, self::VALID_ACTION_TYPES, true)) {
            throw new InvalidArgumentException('Invalid support bot option action type.');
        }

        $this->actionType = $actionType;

        return $this;
    }

    public function getTargetNode(): ?SupportBotNode
    {
        return $this->targetNode;
    }

    public function setTargetNode(?SupportBotNode $targetNode): self
    {
        $this->targetNode = $targetNode;

        return $this;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }

    public function setTargetUrl(?string $targetUrl): self
    {
        $this->targetUrl = $targetUrl;

        return $this;
    }

    public function getTicketCategory(): ?TicketCategory
    {
        return $this->ticketCategory;
    }

    public function setTicketCategory(?TicketCategory $ticketCategory): self
    {
        $this->ticketCategory = $ticketCategory;

        return $this;
    }

    public function getClosingMessage(): ?string
    {
        return $this->closingMessage;
    }

    public function setClosingMessage(?string $closingMessage): self
    {
        $this->closingMessage = $closingMessage;

        return $this;
    }
}
