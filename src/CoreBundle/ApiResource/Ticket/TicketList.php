<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Ticket;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Ticket\TicketListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/ticket/list',
            name: 'get_ticket_list',
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
                        name: 'page',
                        in: 'query',
                        description: 'Page number',
                        required: false,
                        schema: ['type' => 'integer', 'default' => 1],
                    ),
                    new Parameter(
                        name: 'itemsPerPage',
                        in: 'query',
                        description: 'Items per page',
                        required: false,
                        schema: ['type' => 'integer', 'default' => 20],
                    ),
                    new Parameter(
                        name: 'keyword',
                        in: 'query',
                        description: 'Simple ticket search',
                        required: false,
                        schema: ['type' => 'string'],
                    ),
                    new Parameter(
                        name: 'categoryId',
                        in: 'query',
                        description: 'Category filter',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'statusId',
                        in: 'query',
                        description: 'Status filter',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'priorityId',
                        in: 'query',
                        description: 'Priority filter',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'assignedUserId',
                        in: 'query',
                        description: 'Assigned user filter',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'course',
                        in: 'query',
                        description: 'Course title or code filter',
                        required: false,
                        schema: ['type' => 'string'],
                    ),
                    new Parameter(
                        name: 'startDate',
                        in: 'query',
                        description: 'Creation date lower bound',
                        required: false,
                        schema: ['type' => 'string', 'format' => 'date'],
                    ),
                    new Parameter(
                        name: 'endDate',
                        in: 'query',
                        description: 'Creation date upper bound',
                        required: false,
                        schema: ['type' => 'string', 'format' => 'date'],
                    ),
                    new Parameter(
                        name: 'sortField',
                        in: 'query',
                        description: 'Allowed ticket sort field',
                        required: false,
                        schema: ['type' => 'string', 'default' => 'id'],
                    ),
                    new Parameter(
                        name: 'sortDirection',
                        in: 'query',
                        description: 'Sort direction',
                        required: false,
                        schema: ['type' => 'string', 'enum' => ['asc', 'desc'], 'default' => 'desc'],
                    ),
                ],
            ),
            provider: TicketListProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['ticket_list:read']],
)]
final class TicketList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['ticket_list:read'])]
    public string $id = 'ticket_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['ticket_list:read'])]
    public array $items = [];

    /**
     * @var array<int, array{id: int, title: string, description: string|null}>
     */
    #[Groups(['ticket_list:read'])]
    public array $projects = [];

    /**
     * @var array<int, array{id: int, label: string}>
     */
    #[Groups(['ticket_list:read'])]
    public array $categories = [];

    /**
     * @var array<int, array{id: int, label: string, code: string}>
     */
    #[Groups(['ticket_list:read'])]
    public array $statuses = [];

    /**
     * @var array<int, array{id: int, label: string, code: string}>
     */
    #[Groups(['ticket_list:read'])]
    public array $priorities = [];

    /**
     * @var array<int, array{id: int, label: string, username: string}>
     */
    #[Groups(['ticket_list:read'])]
    public array $assignees = [];

    #[Groups(['ticket_list:read'])]
    public int $totalItems = 0;

    #[Groups(['ticket_list:read'])]
    public int $page = 1;

    #[Groups(['ticket_list:read'])]
    public int $itemsPerPage = 20;

    #[Groups(['ticket_list:read'])]
    public int $projectId = 0;

    #[Groups(['ticket_list:read'])]
    public bool $isAdmin = false;

    #[Groups(['ticket_list:read'])]
    public bool $canViewAll = false;

    #[Groups(['ticket_list:read'])]
    public bool $canCreate = false;

    public function getId(): string
    {
        return $this->id;
    }
}
