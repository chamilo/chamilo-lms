<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\SupportBot;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\SupportBot\SupportBotRuntimeAdvance;
use Chamilo\CoreBundle\ApiResource\SupportBot\SupportBotRuntimeStart;
use Chamilo\CoreBundle\Entity\SupportBotConversation;
use Chamilo\CoreBundle\Entity\SupportBotNode;
use Chamilo\CoreBundle\Entity\SupportBotOption;
use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\SupportBot\SupportBotEngine;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<object, object>
 */
final readonly class SupportBotRuntimeProcessor implements ProcessorInterface
{
    public function __construct(
        private SupportBotEngine $engine,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new BadRequestHttpException('An authenticated user is required.');
        }

        if (SupportBotRuntimeStart::OPERATION_START === $operation->getName()) {
            $conversation = $this->engine->startConversation($user);

            return $this->buildStartResponse($conversation);
        }

        if (SupportBotRuntimeAdvance::OPERATION_ADVANCE !== $operation->getName()
            || !$data instanceof SupportBotRuntimeAdvance
        ) {
            throw new BadRequestHttpException('Invalid support bot runtime request.');
        }

        $conversationId = (int) ($uriVariables['conversationId'] ?? 0);
        $optionId = (int) ($data->optionId ?? 0);
        if ($conversationId <= 0 || $optionId <= 0) {
            throw new BadRequestHttpException('A valid conversation and option are required.');
        }

        $result = $this->engine->advanceConversation($user, $conversationId, $optionId);
        $conversation = $result['conversation'];
        $response = new SupportBotRuntimeAdvance();
        $response->conversationId = (int) $conversation->getId();
        $response->outcome = $conversation->getOutcome();
        $response->node = $this->normalizeNode($conversation->getCurrentNode());
        $response->action = (string) $result['action'];
        $response->targetUrl = $result['targetUrl'];
        $response->closingMessage = $result['closingMessage'];

        $ticket = $result['ticket'];
        if ($ticket instanceof Ticket && null !== $ticket->getId()) {
            $response->ticketId = (int) $ticket->getId();
            $response->ticketUrl = '/tickets/'.(int) $ticket->getId();
        }

        return $response;
    }

    private function buildStartResponse(SupportBotConversation $conversation): SupportBotRuntimeStart
    {
        $response = new SupportBotRuntimeStart();
        $response->conversationId = (int) $conversation->getId();
        $response->treeId = (int) $conversation->getTree()->getId();
        $response->treeTitle = $conversation->getTree()->getTitle();
        $response->outcome = $conversation->getOutcome();
        $response->node = $this->normalizeNode($conversation->getCurrentNode());

        return $response;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeNode(?SupportBotNode $node): ?array
    {
        if (!$node instanceof SupportBotNode || null === $node->getId()) {
            return null;
        }

        $options = [];
        foreach ($node->getOptions() as $option) {
            if (!$option instanceof SupportBotOption || null === $option->getId()) {
                continue;
            }
            $options[] = [
                'id' => (int) $option->getId(),
                'label' => $option->getLabel(),
                'actionType' => $option->getActionType(),
            ];
        }

        return [
            'id' => (int) $node->getId(),
            'content' => $node->getContent(),
            'options' => $options,
        ];
    }
}
