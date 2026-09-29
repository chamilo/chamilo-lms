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

final readonly class UpdateTicketTool
{
    public function __construct(
        private McpTicketService $ticketService,
        private TicketWorkflowService $workflowService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'update_ticket',
        description: 'Change the status, priority or assignee of a support ticket on the current Chamilo portal. Only available to administrators who manage this portal. Provide at least one of statusId, priorityId, assignedUserId (0 to unassign) or requestInformation. requestInformation=true moves the ticket to the "Unconfirmed" status to wait for the reporter\'s reply, and cannot be combined with an explicit statusId. This does not post a visible message — use add_ticket_reply to reply, and close_ticket to close the ticket.',
    )]
    public function updateTicket(
        int $ticketId,
        ?int $statusId = null,
        ?int $priorityId = null,
        ?int $assignedUserId = null,
        bool $requestInformation = false,
    ): array {
        try {
            $user = $this->ticketService->assertPortalAdmin();

            if (null === $statusId && null === $priorityId && null === $assignedUserId && !$requestInformation) {
                throw new InvalidArgumentException('At least one of statusId, priorityId, assignedUserId or requestInformation is required.');
            }

            if ($requestInformation && null !== $statusId) {
                throw new InvalidArgumentException('statusId cannot be combined with requestInformation: requesting information always sets the status to "Unconfirmed".');
            }

            $ticket = $this->workflowService->getTicketForCurrentAccessUrl($ticketId);
            $data = ['content' => ''];
            if (null !== $statusId) {
                $data['statusId'] = $statusId;
            }
            if (null !== $priorityId) {
                $data['priorityId'] = $priorityId;
            }
            if (null !== $assignedUserId) {
                $data['assignedUserId'] = $assignedUserId;
            }
            if ($requestInformation) {
                $data['requestConfirmation'] = true;
            }

            $this->workflowService->replyToTicket($ticket, $user, $data, []);

            return [
                'updated' => true,
                'ticket' => $this->ticketService->getTicketDetail($ticket),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The ticket could not be updated because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
