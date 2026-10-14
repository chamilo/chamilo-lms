<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'support_bot_step')]
#[ORM\Index(columns: ['conversation_id', 'created_at'], name: 'idx_support_bot_step_conv_created')]
class SupportBotStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SupportBotConversation::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected SupportBotConversation $conversation;

    #[ORM\ManyToOne(targetEntity: SupportBotNode::class)]
    #[ORM\JoinColumn(name: 'node_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?SupportBotNode $node = null;

    #[ORM\ManyToOne(targetEntity: SupportBotOption::class)]
    #[ORM\JoinColumn(name: 'selected_option_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?SupportBotOption $selectedOption = null;

    #[ORM\Column(name: 'node_content', type: 'text')]
    protected string $nodeContent = '';

    #[ORM\Column(name: 'selected_option_label', type: 'string', length: 255, nullable: true)]
    protected ?string $selectedOptionLabel = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): SupportBotConversation
    {
        return $this->conversation;
    }

    public function setConversation(SupportBotConversation $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getNode(): ?SupportBotNode
    {
        return $this->node;
    }

    public function setNode(?SupportBotNode $node): self
    {
        $this->node = $node;

        return $this;
    }

    public function getSelectedOption(): ?SupportBotOption
    {
        return $this->selectedOption;
    }

    public function setSelectedOption(?SupportBotOption $selectedOption): self
    {
        $this->selectedOption = $selectedOption;
        $this->selectedOptionLabel = $selectedOption?->getLabel();

        return $this;
    }

    public function getNodeContent(): string
    {
        return $this->nodeContent;
    }

    public function setNodeContent(string $nodeContent): self
    {
        $this->nodeContent = $nodeContent;

        return $this;
    }

    public function getSelectedOptionLabel(): ?string
    {
        return $this->selectedOptionLabel;
    }

    public function setSelectedOptionLabel(?string $selectedOptionLabel): self
    {
        $this->selectedOptionLabel = $selectedOptionLabel;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
