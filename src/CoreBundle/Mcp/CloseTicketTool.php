<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Mcp\McpTicketService;
use Chamilo\CoreBundle\Service\Ticket\TicketWorkflowService;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class CloseTicketTool
{
    public function __construct(
        private McpTicketService $ticketService,
        private TicketWorkflowService $workflowService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'close_ticket',
        description: 'Close a support ticket on the current Chamilo portal. Only available to administrators who manage this portal. Calling this on an already-closed ticket is a no-op.',
    )]
    public function closeTicket(int $ticketId): array
    {
        try {
            $user = $this->ticketService->assertPortalAdmin();
            $ticket = $this->workflowService->getTicketForCurrentAccessUrl($ticketId);
            $this->workflowService->close($ticket, $user);

            return [
                'closed' => true,
                'ticket' => $this->ticketService->getTicketDetail($ticket),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The ticket could not be closed because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
