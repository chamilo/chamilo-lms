<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Ticket;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\Ticket\TicketDetailProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/ticket/{id}',
            requirements: ['id' => '\d+'],
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_ticket_detail',
            provider: TicketDetailProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['ticket_detail:read']],
)]
final class TicketDetail
{
    #[ApiProperty(identifier: true)]
    #[Groups(['ticket_detail:read'])]
    public int $id = 0;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['ticket_detail:read'])]
    public array $ticket = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['ticket_detail:read'])]
    public array $messages = [];

    /**
     * @var array<int, array{id: int, label: string, code: string}>
     */
    #[Groups(['ticket_detail:read'])]
    public array $statuses = [];

    /**
     * @var array<int, array{id: int, label: string, code: string}>
     */
    #[Groups(['ticket_detail:read'])]
    public array $priorities = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['ticket_detail:read'])]
    public array $assignmentHistory = [];

    #[Groups(['ticket_detail:read'])]
    public bool $isAdmin = false;

    #[Groups(['ticket_detail:read'])]
    public bool $canReply = false;

    #[Groups(['ticket_detail:read'])]
    public bool $canClose = false;

    #[Groups(['ticket_detail:read'])]
    public bool $canManage = false;

    #[Groups(['ticket_detail:read'])]
    public bool $confirmationPending = false;

    #[Groups(['ticket_detail:read'])]
    public bool $canConfirm = false;

    #[Groups(['ticket_detail:read'])]
    public bool $isSubscribed = false;

    #[Groups(['ticket_detail:read'])]
    public bool $showLearningPathInfo = false;

    #[Groups(['ticket_detail:read'])]
    public int $maxUploadSize = 0;

    #[Groups(['ticket_detail:read'])]
    public int $maxAttachments = 6;

    #[Groups(['ticket_detail:read'])]
    public string $csrfToken = '';

    #[Groups(['ticket_detail:read'])]
    public string $legacyUrl = '';

    public function getId(): int
    {
        return $this->id;
    }
}
