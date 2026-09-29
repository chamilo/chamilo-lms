<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Mcp\McpTicketService;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class ListTicketsTool
{
    public function __construct(
        private McpTicketService $ticketService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'list_tickets',
        description: 'List support tickets for the current Chamilo portal. Only available to administrators who manage this portal. Filter by projectId (defaults to the portal\'s first project), statusId, priorityId, categoryId or assignedUserId (0 = unassigned), or search with keyword. The result also lists the portal\'s projects/categories/statuses/priorities so a project or filter value can be picked before drilling down with get_ticket.',
    )]
    public function listTickets(
        ?int $projectId = null,
        ?int $statusId = null,
        ?int $priorityId = null,
        ?int $categoryId = null,
        ?int $assignedUserId = null,
        ?string $keyword = null,
        int $page = 1,
        int $itemsPerPage = 20,
        string $sortField = 'id',
        string $sortDirection = 'desc',
    ): array {
        try {
            $this->ticketService->assertPortalAdmin();

            return $this->ticketService->listTickets(
                $projectId,
                $statusId,
                $priorityId,
                $categoryId,
                $assignedUserId,
                $keyword,
                $page,
                $itemsPerPage,
                $sortField,
                $sortDirection,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The ticket list could not be retrieved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
