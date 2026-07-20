<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Ticket;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Ticket\TicketFormProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/ticket/form',
            name: 'get_ticket_form',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'projectId',
                        in: 'query',
                        description: 'Ticket project id',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'sessionId',
                        in: 'query',
                        description: 'Selected session id used to load courses',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
            provider: TicketFormProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['ticket_form:read']],
)]
final class TicketForm
{
    #[ApiProperty(identifier: true)]
    #[Groups(['ticket_form:read'])]
    public string $id = 'ticket_form';

    /**
     * @var array<int, array{id: int, title: string, description: string|null}>
     */
    #[Groups(['ticket_form:read'])]
    public array $projects = [];

    /**
     * @var array<int, array{id: int, label: string, description: string|null, courseRequired: bool}>
     */
    #[Groups(['ticket_form:read'])]
    public array $categories = [];

    /**
     * @var array<int, array{id: int, label: string, code: string}>
     */
    #[Groups(['ticket_form:read'])]
    public array $statuses = [];

    /**
     * @var array<int, array{id: int, label: string, code: string}>
     */
    #[Groups(['ticket_form:read'])]
    public array $priorities = [];

    /**
     * @var array<int, array{id: string, label: string}>
     */
    #[Groups(['ticket_form:read'])]
    public array $sources = [];

    /**
     * @var array<int, array{id: int, label: string}>
     */
    #[Groups(['ticket_form:read'])]
    public array $sessions = [];

    /**
     * @var array<int, array{id: int, label: string, code: string}>
     */
    #[Groups(['ticket_form:read'])]
    public array $courses = [];

    #[Groups(['ticket_form:read'])]
    public int $projectId = 0;

    #[Groups(['ticket_form:read'])]
    public int $sessionId = 0;

    #[Groups(['ticket_form:read'])]
    public int $defaultPriorityId = 1;

    #[Groups(['ticket_form:read'])]
    public int $defaultStatusId = 1;

    #[Groups(['ticket_form:read'])]
    public string $defaultSource = 'PLA';

    #[Groups(['ticket_form:read'])]
    public int $maxUploadSize = 0;

    #[Groups(['ticket_form:read'])]
    public int $maxAttachments = 6;

    #[Groups(['ticket_form:read'])]
    public bool $isAdmin = false;

    #[Groups(['ticket_form:read'])]
    public bool $canCreate = false;

    #[Groups(['ticket_form:read'])]
    public string $csrfToken = '';

    public function getId(): string
    {
        return $this->id;
    }
}
