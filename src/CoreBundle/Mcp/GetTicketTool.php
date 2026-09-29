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

final readonly class GetTicketTool
{
    public function __construct(
        private McpTicketService $ticketService,
        private TicketWorkflowService $workflowService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'get_ticket',
        description: 'Read one support ticket on the current Chamilo portal by id, including its full message thread and attachments. Only available to administrators who manage this portal.',
    )]
    public function getTicket(int $ticketId): array
    {
        try {
            $this->ticketService->assertPortalAdmin();
            $ticket = $this->workflowService->getTicketForCurrentAccessUrl($ticketId);

            return $this->ticketService->getTicketDetail($ticket);
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The ticket could not be retrieved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
