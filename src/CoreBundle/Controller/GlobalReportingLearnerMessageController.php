<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\EventLoggerHelper;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContextResolver;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingQueryService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use UserManager;

use const ENT_QUOTES;

/**
 * Follow-up messages about a learner, written on the learner report (formerly the "Messages" block of
 * main/my_space/myStudents.php). They exist only while message.private_messages_about_user is on, and
 * whoever may open the learner report may write them, as in the legacy page.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GlobalReportingLearnerMessageController extends BaseController
{
    public function __construct(
        private readonly GlobalReportingContextResolver $contextResolver,
        private readonly GlobalReportingQueryService $queryService,
        private readonly SettingsManager $settingsManager,
        private readonly UserRepository $userRepository,
        private readonly MessageRepository $messageRepository,
        private readonly MessageHelper $messageHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly UserHelper $userHelper,
        private readonly EventLoggerHelper $eventLoggerHelper,
    ) {}

    #[Route('/global-reporting/learners/{userId}/messages', name: 'global_reporting_learner_message_send', requirements: ['userId' => '\d+'], methods: ['POST'])]
    public function send(int $userId, Request $request): JsonResponse
    {
        $learner = $this->findLearner($userId);
        $sender = $this->userHelper->getCurrent();

        $payload = json_decode($request->getContent(), true);
        $subject = trim((string) (\is_array($payload) ? ($payload['subject'] ?? '') : ''));
        $content = trim((string) (\is_array($payload) ? ($payload['content'] ?? '') : ''));
        if ('' === $subject || '' === $content) {
            throw new BadRequestHttpException('All fields are required.');
        }

        $message = (new Message())
            ->setTitle($subject)
            ->setContent($content)
            ->setSender($sender)
            ->addReceiverTo($learner)
            ->setMsgType(Message::MESSAGE_TYPE_CONVERSATION)
        ;
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // As in the legacy page, the learner's superiors are told a message was written about them.
        $learnerName = UserManager::formatUserFullName($learner);
        $url = $request->getSchemeAndHttpHost().$request->getBasePath().'/reporting/learners/'.$learner->getId();
        foreach (UserManager::getStudentBossList($learner->getId()) as $boss) {
            if ((int) $boss['boss_id'] === $sender->getId()) {
                continue;
            }

            $this->messageHelper->sendMessage(
                (int) $boss['boss_id'],
                \sprintf($this->translator->trans('Follow up message about student %s'), $learnerName),
                \sprintf(
                    $this->translator->trans('Hi,<br/><br/>User %s sent a follow up message about student %s.<br/><br/>The message can be seen here %s'),
                    htmlspecialchars(UserManager::formatUserFullName($sender), ENT_QUOTES),
                    htmlspecialchars($learnerName, ENT_QUOTES),
                    '<a href="'.htmlspecialchars($url, ENT_QUOTES).'">'.htmlspecialchars($url, ENT_QUOTES).'</a>'
                ),
                senderId: $sender->getId(),
                forceTitleWhenSendingEmail: true,
            );
        }

        return $this->json(['id' => $message->getId()]);
    }

    #[Route('/global-reporting/learners/{userId}/messages/{messageId}', name: 'global_reporting_learner_message_delete', requirements: ['userId' => '\d+', 'messageId' => '\d+'], methods: ['DELETE'])]
    public function delete(int $userId, int $messageId): JsonResponse
    {
        $learner = $this->findLearner($userId);
        $message = $this->messageRepository->find($messageId);

        // Only the author deletes a message, and only one written about this learner.
        if (
            !$message instanceof Message
            || Message::MESSAGE_TYPE_CONVERSATION !== $message->getMsgType()
            || $message->getSender()?->getId() !== $this->userHelper->getCurrent()?->getId()
            || [] === array_filter(
                $message->getReceiversTo(),
                static fn (MessageRelUser $receiver): bool => $receiver->getReceiver()->getId() === $learner->getId()
            )
        ) {
            throw new NotFoundHttpException('Message not found.');
        }

        // Same event as the legacy page (LOG_MESSAGE_DELETE / LOG_MESSAGE_DATA).
        $this->eventLoggerHelper->addEvent(
            'msg_deleted',
            'message_data',
            $message->getId().' - '.$message->getTitle(),
            courseId: 0,
            sessionId: 0,
        );
        $this->messageRepository->delete($message);

        return $this->json(['id' => $messageId]);
    }

    private function findLearner(int $userId): User
    {
        if ('true' !== $this->settingsManager->getSetting('message.private_messages_about_user', true)) {
            throw new AccessDeniedHttpException('Messages about users are disabled.');
        }

        $context = $this->contextResolver->resolve();
        if (
            !($context->canViewGlobalReports || $context->isStudentBoss)
            || $context->currentUserId() === $userId
            || !$this->queryService->isUserInScope($context, $userId)
        ) {
            throw new AccessDeniedHttpException('The requested learner is outside your reporting scope.');
        }

        $learner = $this->userRepository->find($userId);
        if (!$learner instanceof User) {
            throw new NotFoundHttpException('User not found.');
        }

        return $learner;
    }
}
