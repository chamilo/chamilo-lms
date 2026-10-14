<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\SupportBot;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\SupportBotConversation;
use Chamilo\CoreBundle\Entity\SupportBotNode;
use Chamilo\CoreBundle\Entity\SupportBotOption;
use Chamilo\CoreBundle\Entity\SupportBotStep;
use Chamilo\CoreBundle\Entity\SupportBotTree;
use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\SupportBotTreeRepository;
use Chamilo\CoreBundle\Service\Ticket\TicketWorkflowService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const ENT_QUOTES;

final readonly class SupportBotEngine
{
    private const string SETTING_ENABLED = 'ticket.ticket_support_bot_enabled';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private AccessUrlHelper $accessUrlHelper,
        private SupportBotTreeRepository $treeRepository,
        private TicketWorkflowService $ticketWorkflowService,
    ) {}

    public function isAvailable(): bool
    {
        if ('true' !== $this->settingsManager->getSetting(self::SETTING_ENABLED)) {
            return false;
        }

        $accessUrl = $this->getCurrentAccessUrl();
        $tree = $this->treeRepository->findPublishedForAccessUrl($accessUrl);

        $entryNode = $tree?->getEntryNode();

        return $tree instanceof SupportBotTree
            && $entryNode instanceof SupportBotNode
            && $entryNode->getTree()->getId() === $tree->getId()
            && !$entryNode->getOptions()->isEmpty();
    }

    public function startConversation(User $user): SupportBotConversation
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $tree = $this->requirePublishedTree($accessUrl);
        $entryNode = $tree->getEntryNode();

        if (!$entryNode instanceof SupportBotNode
            || $entryNode->getTree()->getId() !== $tree->getId()
            || $entryNode->getOptions()->isEmpty()
        ) {
            throw new ConflictHttpException('The published support bot tree has no valid entry node.');
        }

        $conversation = (new SupportBotConversation())
            ->setTree($tree)
            ->setTreeVersion($tree->getVersion())
            ->setUser($user)
            ->setAccessUrl($accessUrl)
            ->setCurrentNode($entryNode)
            ->setOutcome(SupportBotConversation::OUTCOME_IN_PROGRESS)
        ;
        $conversation->addStep($this->createStep($entryNode));

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $conversation;
    }

    /**
     * @return array{
     *     conversation: SupportBotConversation,
     *     action: string,
     *     targetUrl: ?string,
     *     closingMessage: ?string,
     *     ticket: ?Ticket
     * }
     */
    public function advanceConversation(User $user, int $conversationId, int $optionId): array
    {
        $conversation = $this->entityManager->getRepository(SupportBotConversation::class)->find($conversationId);
        if (!$conversation instanceof SupportBotConversation
            || $conversation->getUser()->getId() !== $user->getId()
        ) {
            throw new NotFoundHttpException('The support bot conversation was not found.');
        }

        $accessUrl = $this->getCurrentAccessUrl();
        if ($conversation->getAccessUrl()?->getId() !== $accessUrl->getId()) {
            throw new NotFoundHttpException('The support bot conversation was not found.');
        }

        $currentNode = $conversation->getCurrentNode();
        if (SupportBotConversation::OUTCOME_IN_PROGRESS !== $conversation->getOutcome()
            || !$currentNode instanceof SupportBotNode
        ) {
            throw new ConflictHttpException('The support bot conversation has already ended.');
        }

        $option = $this->entityManager->getRepository(SupportBotOption::class)->find($optionId);
        if (!$option instanceof SupportBotOption
            || $option->getNode()->getId() !== $currentNode->getId()
            || $option->getNode()->getTree()->getId() !== $conversation->getTree()->getId()
        ) {
            throw new NotFoundHttpException('The support bot option was not found for the current step.');
        }

        $step = $conversation->getLastStep();
        if (!$step instanceof SupportBotStep
            || $step->getNode()?->getId() !== $currentNode->getId()
            || $step->getSelectedOption() instanceof SupportBotOption
        ) {
            throw new ConflictHttpException('The support bot conversation step is inconsistent.');
        }
        $step->setSelectedOption($option);

        $result = [
            'conversation' => $conversation,
            'action' => $option->getActionType(),
            'targetUrl' => null,
            'closingMessage' => null,
            'ticket' => null,
        ];

        switch ($option->getActionType()) {
            case SupportBotOption::ACTION_GOTO_NODE:
                $targetNode = $option->getTargetNode();
                if (!$targetNode instanceof SupportBotNode
                    || $targetNode->getTree()->getId() !== $conversation->getTree()->getId()
                ) {
                    throw new ConflictHttpException('The support bot option points to an invalid target node.');
                }

                $conversation->setCurrentNode($targetNode);
                $conversation->addStep($this->createStep($targetNode));

                break;

            case SupportBotOption::ACTION_OPEN_LINK:
                $targetUrl = trim((string) $option->getTargetUrl());
                if (!$this->isSafeRelativeUrl($targetUrl)) {
                    throw new ConflictHttpException('The support bot option contains an unsafe link.');
                }

                $result['targetUrl'] = $targetUrl;
                $conversation->addStep($this->createStep($currentNode));

                break;

            case SupportBotOption::ACTION_END_CONVERSATION:
                $conversation
                    ->setOutcome(SupportBotConversation::OUTCOME_RESOLVED)
                    ->setCurrentNode(null)
                    ->setEndedAt(new DateTime())
                ;
                $result['closingMessage'] = $option->getClosingMessage();

                break;

            case SupportBotOption::ACTION_ESCALATE_TICKET:
                $ticket = $this->escalateToTicket($conversation, $option, $user);
                $conversation
                    ->setOutcome(SupportBotConversation::OUTCOME_ESCALATED)
                    ->setCurrentNode(null)
                    ->setResultingTicket($ticket)
                    ->setEndedAt(new DateTime())
                ;
                $result['ticket'] = $ticket;

                break;

            default:
                throw new ConflictHttpException('The support bot option action is not supported.');
        }

        $this->entityManager->flush();

        return $result;
    }

    private function requirePublishedTree(AccessUrl $accessUrl): SupportBotTree
    {
        if ('true' !== $this->settingsManager->getSetting(self::SETTING_ENABLED)) {
            throw new NotFoundHttpException('The support bot is unavailable.');
        }

        $tree = $this->treeRepository->findPublishedForAccessUrl($accessUrl);
        if (!$tree instanceof SupportBotTree) {
            throw new NotFoundHttpException('No published support bot tree is available.');
        }

        return $tree;
    }

    private function getCurrentAccessUrl(): AccessUrl
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new ConflictHttpException('The current access URL is required.');
        }

        return $accessUrl;
    }

    private function createStep(SupportBotNode $node): SupportBotStep
    {
        return (new SupportBotStep())
            ->setNode($node)
            ->setNodeContent($node->getContent())
        ;
    }

    private function escalateToTicket(
        SupportBotConversation $conversation,
        SupportBotOption $option,
        User $user,
    ): Ticket {
        $category = $option->getTicketCategory();
        if (!$category instanceof TicketCategory) {
            throw new ConflictHttpException('A ticket category is required for support bot escalation.');
        }
        if ($category->isCourseRequired()) {
            throw new ConflictHttpException('The support bot cannot escalate to a course-required ticket category.');
        }

        $project = $category->getProject();
        $defaultProject = $conversation->getTree()->getDefaultTicketProject();
        if (null !== $defaultProject && $defaultProject->getId() !== $project->getId()) {
            throw new ConflictHttpException('The support bot ticket category does not belong to the configured project.');
        }

        return $this->ticketWorkflowService->createTicketFromSupportBot(
            $user,
            [
                'projectId' => (int) $project->getId(),
                'categoryId' => (int) $category->getId(),
                'subject' => 'Support Bot: '.$conversation->getTree()->getTitle(),
                'content' => $this->buildTicketTranscript($conversation),
            ],
        );
    }

    private function buildTicketTranscript(SupportBotConversation $conversation): string
    {
        $parts = [];
        foreach ($conversation->getSteps() as $step) {
            $nodeContent = htmlspecialchars($step->getNodeContent(), ENT_QUOTES, 'UTF-8');
            $selectedOptionLabel = $step->getSelectedOptionLabel();
            $line = '<p>'.$nodeContent;
            if (null !== $selectedOptionLabel && '' !== trim($selectedOptionLabel)) {
                $line .= '<br>→ '.htmlspecialchars($selectedOptionLabel, ENT_QUOTES, 'UTF-8');
            }
            $parts[] = $line.'</p>';
        }

        return implode('', $parts);
    }

    private function isSafeRelativeUrl(string $targetUrl): bool
    {
        return str_starts_with($targetUrl, '/') && !str_starts_with($targetUrl, '//');
    }
}
