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

final readonly class AddTicketReplyTool
{
    public function __construct(
        private McpTicketService $ticketService,
        private TicketWorkflowService $workflowService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'add_ticket_reply',
        description: 'Post a reply message on a support ticket on the current Chamilo portal, visible to the reporter and any subscribed users. Only available to administrators who manage this portal. Use update_ticket to change status/priority/assignee without posting a visible reply, and close_ticket to close the ticket.',
    )]
    public function addTicketReply(int $ticketId, string $content, ?string $subject = null): array
    {
        try {
            $user = $this->ticketService->assertPortalAdmin();

            if ('' === trim(strip_tags($content))) {
                throw new InvalidArgumentException('The reply content cannot be empty.');
            }

            $ticket = $this->workflowService->getTicketForCurrentAccessUrl($ticketId);
            $message = $this->workflowService->replyToTicket(
                $ticket,
                $user,
                ['content' => $content, 'subject' => $subject ?? ''],
                [],
            );

            return [
                'message_id' => (int) $message->getId(),
                'ticket' => $this->ticketService->getTicketDetail($ticket),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The reply could not be added because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
