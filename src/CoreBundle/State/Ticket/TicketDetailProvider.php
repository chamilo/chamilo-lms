<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Ticket;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Ticket\TicketDetail;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketAssignedLog;
use Chamilo\CoreBundle\Entity\TicketMessage;
use Chamilo\CoreBundle\Entity\TicketMessageAttachment;
use Chamilo\CoreBundle\Entity\TicketPriority;
use Chamilo\CoreBundle\Entity\TicketStatus;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\TicketProjectHelper;
use Chamilo\CoreBundle\Repository\Node\TicketMessageAttachmentRepository;
use Chamilo\CoreBundle\Repository\TicketRelUserRepository;
use Chamilo\CoreBundle\Service\Ticket\TicketWorkflowService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const COURSEMANAGERLOWSECURITY;
use const DATE_ATOM;

/**
 * @implements ProviderInterface<TicketDetail>
 */
final readonly class TicketDetailProvider implements ProviderInterface
{
    private const STATUS_UNCONFIRMED_ID = 3;
    private const STATUS_CLOSED_ID = 4;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private AccessUrlHelper $accessUrlHelper,
        private TicketProjectHelper $ticketProjectHelper,
        private TicketMessageAttachmentRepository $attachmentRepository,
        private TicketRelUserRepository $ticketRelUserRepository,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TicketDetail
    {
        $ticketId = (int) ($uriVariables['id'] ?? 0);
        if ($ticketId <= 0) {
            throw new BadRequestHttpException('A valid ticket id is required.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new BadRequestHttpException('The authenticated user is required.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new BadRequestHttpException('The current access URL is required.');
        }

        $ticketEntity = $this->entityManager->getRepository(Ticket::class)->find($ticketId);
        if (!$ticketEntity instanceof Ticket) {
            throw new NotFoundHttpException('The requested ticket was not found.');
        }

        $row = $this->getTicketRow($ticketId, (int) $accessUrl->getId());
        if (null === $row) {
            throw new NotFoundHttpException('The requested ticket was not found for this access URL.');
        }

        $userId = (int) $user->getId();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $canViewAll = $this->ticketProjectHelper->userIsAllowInProject((int) $row['projectId']);
        $isCreator = $userId === (int) $row['creatorId'];
        $isAssignee = null !== $row['assignedUserId'] && $userId === (int) $row['assignedUserId'];

        if (!$isAdmin && !$canViewAll && !$isCreator && !$isAssignee) {
            throw new AccessDeniedHttpException('You are not allowed to view this ticket.');
        }

        $result = new TicketDetail();
        $result->id = $ticketId;
        $result->ticket = $this->normalizeTicket($row);
        $result->messages = $this->getMessages($ticketEntity);
        $result->isAdmin = $isAdmin;
        $result->canReply = $isAdmin
            || (($isCreator || $isAssignee) && self::STATUS_CLOSED_ID !== (int) $row['statusId']);
        $result->canClose = $isAdmin || $isCreator || $isAssignee;
        $result->canManage = $isAdmin;
        $result->confirmationPending = self::STATUS_UNCONFIRMED_ID === (int) $row['statusId'];
        $result->canConfirm = $isCreator && $result->confirmationPending;
        $result->isSubscribed = $this->ticketRelUserRepository->isUserSubscribedToTicket($user, $ticketEntity);
        $result->showLearningPathInfo = 'true' === $this->settingsManager->getSetting(
            'lp.ticket_lp_quiz_info_add',
        );
        $result->maxUploadSize = max(
            0,
            (int) $this->settingsManager->getSetting('message.message_max_upload_filesize'),
        );
        $result->csrfToken = $this->csrfTokenManager
            ->getToken(TicketWorkflowService::CSRF_TOKEN_ID)
            ->getValue()
        ;
        $result->statuses = $isAdmin ? $this->getStatusOptions($accessUrl) : [];
        $result->priorities = $isAdmin ? $this->getPriorityOptions($accessUrl) : [];
        $result->assignmentHistory = $isAdmin ? $this->getAssignmentHistory($ticketId) : [];
        $result->legacyUrl = '/main/ticket/ticket_details.php?ticket_id='.$ticketId;

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getTicketRow(int $ticketId, int $accessUrlId): ?array
    {
        $row = $this->entityManager->createQueryBuilder()
            ->select([
                'ticket.id AS id',
                'ticket.code AS code',
                'ticket.subject AS subject',
                'ticket.message AS message',
                'ticket.startDate AS createdAt',
                'ticket.endDate AS closedAt',
                'ticket.lastEditDateTime AS updatedAt',
                'ticket.totalMessages AS totalMessages',
                'ticket.insertUserId AS creatorId',
                'ticket.exerciseId AS exerciseId',
                'ticket.lpId AS lpId',
                'project.id AS projectId',
                'project.title AS projectTitle',
                'category.id AS categoryId',
                'category.title AS categoryTitle',
                'priority.id AS priorityId',
                'priority.title AS priorityTitle',
                'priority.code AS priorityCode',
                'status.id AS statusId',
                'status.title AS statusTitle',
                'status.code AS statusCode',
                'creator.username AS creatorUsername',
                'creator.firstname AS creatorFirstname',
                'creator.lastname AS creatorLastname',
                'assigned.id AS assignedUserId',
                'assigned.username AS assignedUsername',
                'assigned.firstname AS assignedFirstname',
                'assigned.lastname AS assignedLastname',
                'course.id AS courseId',
                'course.title AS courseTitle',
                'course.code AS courseCode',
                'session.id AS sessionId',
                'session.title AS sessionTitle',
            ])
            ->from(Ticket::class, 'ticket')
            ->innerJoin('ticket.project', 'project')
            ->innerJoin('ticket.category', 'category')
            ->innerJoin('ticket.priority', 'priority')
            ->innerJoin('ticket.status', 'status')
            ->leftJoin(User::class, 'creator', Join::WITH, 'creator.id = ticket.insertUserId')
            ->leftJoin('ticket.assignedLastUser', 'assigned')
            ->leftJoin('ticket.course', 'course')
            ->leftJoin('ticket.session', 'session')
            ->andWhere('ticket.id = :ticketId')
            ->andWhere('IDENTITY(ticket.accessUrl) = :accessUrlId')
            ->setParameter('ticketId', $ticketId, Types::INTEGER)
            ->setParameter('accessUrlId', $accessUrlId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return \is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeTicket(array $row): array
    {
        $courseId = null !== $row['courseId'] ? (int) $row['courseId'] : null;
        $sessionId = null !== $row['sessionId'] ? (int) $row['sessionId'] : null;
        $courseCode = null !== $row['courseCode'] ? (string) $row['courseCode'] : null;
        $exerciseId = null !== $row['exerciseId'] ? (int) $row['exerciseId'] : null;
        $lpId = null !== $row['lpId'] ? (int) $row['lpId'] : null;

        return [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'subject' => (string) $row['subject'],
            'messageHtml' => (string) \Security::remove_XSS((string) ($row['message'] ?? ''), COURSEMANAGERLOWSECURITY),
            'createdAt' => $this->formatDate($row['createdAt'] ?? null),
            'updatedAt' => $this->formatDate($row['updatedAt'] ?? null),
            'closedAt' => $this->formatDate($row['closedAt'] ?? null),
            'totalMessages' => (int) $row['totalMessages'],
            'project' => [
                'id' => (int) $row['projectId'],
                'title' => (string) $row['projectTitle'],
            ],
            'category' => [
                'id' => (int) $row['categoryId'],
                'title' => (string) $row['categoryTitle'],
            ],
            'priority' => [
                'id' => (int) $row['priorityId'],
                'title' => (string) $row['priorityTitle'],
                'code' => (string) $row['priorityCode'],
            ],
            'status' => [
                'id' => (int) $row['statusId'],
                'title' => (string) $row['statusTitle'],
                'code' => (string) $row['statusCode'],
            ],
            'creator' => [
                'id' => (int) $row['creatorId'],
                'username' => (string) ($row['creatorUsername'] ?? ''),
                'fullName' => $this->buildUserLabel([
                    'firstname' => $row['creatorFirstname'] ?? '',
                    'lastname' => $row['creatorLastname'] ?? '',
                    'username' => $row['creatorUsername'] ?? '',
                ]),
            ],
            'assignee' => null !== $row['assignedUserId'] ? [
                'id' => (int) $row['assignedUserId'],
                'username' => (string) ($row['assignedUsername'] ?? ''),
                'fullName' => $this->buildUserLabel([
                    'firstname' => $row['assignedFirstname'] ?? '',
                    'lastname' => $row['assignedLastname'] ?? '',
                    'username' => $row['assignedUsername'] ?? '',
                ]),
            ] : null,
            'course' => null !== $courseId ? [
                'id' => $courseId,
                'title' => (string) ($row['courseTitle'] ?? ''),
                'code' => $courseCode,
                'url' => '/course/'.$courseId.'/home'.(null !== $sessionId ? '?sid='.$sessionId : ''),
            ] : null,
            'session' => null !== $sessionId ? [
                'id' => $sessionId,
                'title' => (string) ($row['sessionTitle'] ?? ''),
            ] : null,
            'exercise' => null !== $exerciseId && null !== $courseCode ? [
                'id' => $exerciseId,
                'url' => '/main/exercise/overview.php?'.http_build_query([
                    'cidReq' => $courseCode,
                    'id_session' => $sessionId ?? 0,
                    'exerciseId' => $exerciseId,
                ]),
            ] : null,
            'learningPath' => null !== $lpId && null !== $courseCode ? [
                'id' => $lpId,
                'url' => '/main/lp/lp_controller.php?'.http_build_query([
                    'cidReq' => $courseCode,
                    'id_session' => $sessionId ?? 0,
                    'lp_id' => $lpId,
                    'action' => 'view',
                ]),
            ] : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMessages(Ticket $ticket): array
    {
        $ticketId = (int) $ticket->getId();
        $rows = $this->entityManager->createQueryBuilder()
            ->select([
                'message.id AS id',
                'message.subject AS subject',
                'message.message AS messageContent',
                'message.status AS status',
                'message.insertDateTime AS createdAt',
                'author.id AS authorId',
                'author.username AS authorUsername',
                'author.firstname AS authorFirstname',
                'author.lastname AS authorLastname',
            ])
            ->from(TicketMessage::class, 'message')
            ->innerJoin(User::class, 'author', Join::WITH, 'author.id = message.insertUserId')
            ->andWhere('IDENTITY(message.ticket) = :ticketId')
            ->andWhere('author.active <> :softDeleted')
            ->setParameter('ticketId', $ticketId, Types::INTEGER)
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ->orderBy('message.insertDateTime', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $attachmentsByMessage = $this->getAttachmentsByMessage($ticket);
        $messages = [];

        foreach ($rows as $index => $row) {
            $messageId = (int) $row['id'];
            $messages[] = [
                'id' => $messageId,
                'number' => $index + 1,
                'subject' => (string) ($row['subject'] ?? ''),
                'messageHtml' => (string) \Security::remove_XSS(
                    (string) ($row['messageContent'] ?? ''),
                    COURSEMANAGERLOWSECURITY,
                ),
                'status' => (string) $row['status'],
                'createdAt' => $this->formatDate($row['createdAt'] ?? null),
                'author' => [
                    'id' => (int) $row['authorId'],
                    'username' => (string) $row['authorUsername'],
                    'fullName' => $this->buildUserLabel([
                        'firstname' => $row['authorFirstname'] ?? '',
                        'lastname' => $row['authorLastname'] ?? '',
                        'username' => $row['authorUsername'] ?? '',
                    ]),
                ],
                'attachments' => $attachmentsByMessage[$messageId] ?? [],
            ];
        }

        return $messages;
    }

    /**
     * @return array<int, array<int, array{id: int, filename: string, size: int, url: string}>>
     */
    private function getAttachmentsByMessage(Ticket $ticket): array
    {
        $attachments = $this->attachmentRepository->findBy(['ticket' => $ticket]);
        $result = [];

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof TicketMessageAttachment || null === $attachment->getId()) {
                continue;
            }

            $messageId = (int) $attachment->getMessage()->getId();
            $result[$messageId][] = [
                'id' => (int) $attachment->getId(),
                'filename' => (string) $attachment->getFilename(),
                'size' => (int) $attachment->getSize(),
                'url' => $this->attachmentRepository->getResourceFileDownloadUrl($attachment),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{id: int, label: string, code: string}>
     */
    private function getStatusOptions(AccessUrl $accessUrl): array
    {
        $repository = $this->entityManager->getRepository(TicketStatus::class);
        $statuses = $repository->findBy(['accessUrl' => $accessUrl], ['title' => 'ASC']);
        if ([] === $statuses) {
            $statuses = $repository->findBy(['accessUrl' => null], ['title' => 'ASC']);
        }

        $result = [];
        foreach ($statuses as $status) {
            if ($status instanceof TicketStatus && null !== $status->getId()) {
                $result[] = [
                    'id' => (int) $status->getId(),
                    'label' => (string) $status->getTitle(),
                    'code' => (string) $status->getCode(),
                ];
            }
        }

        return $result;
    }

    /**
     * @return array<int, array{id: int, label: string, code: string}>
     */
    private function getPriorityOptions(AccessUrl $accessUrl): array
    {
        $repository = $this->entityManager->getRepository(TicketPriority::class);
        $priorities = $repository->findBy(['accessUrl' => $accessUrl], ['title' => 'ASC']);
        if ([] === $priorities) {
            $priorities = $repository->findBy(['accessUrl' => null], ['title' => 'ASC']);
        }

        $result = [];
        foreach ($priorities as $priority) {
            if ($priority instanceof TicketPriority && null !== $priority->getId()) {
                $result[] = [
                    'id' => (int) $priority->getId(),
                    'label' => (string) $priority->getTitle(),
                    'code' => (string) $priority->getCode(),
                ];
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAssignmentHistory(int $ticketId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select([
                'log.id AS id',
                'log.assignedDate AS assignedAt',
                'assigned.id AS assignedId',
                'assigned.username AS assignedUsername',
                'assigned.firstname AS assignedFirstname',
                'assigned.lastname AS assignedLastname',
                'actor.id AS actorId',
                'actor.username AS actorUsername',
                'actor.firstname AS actorFirstname',
                'actor.lastname AS actorLastname',
            ])
            ->from(TicketAssignedLog::class, 'log')
            ->leftJoin('log.user', 'assigned')
            ->leftJoin(User::class, 'actor', Join::WITH, 'actor.id = log.insertUserId')
            ->andWhere('IDENTITY(log.ticket) = :ticketId')
            ->setParameter('ticketId', $ticketId, Types::INTEGER)
            ->orderBy('log.assignedDate', 'DESC')
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) $row['id'],
                'assignedAt' => $this->formatDate($row['assignedAt'] ?? null),
                'assignee' => null !== $row['assignedId'] ? [
                    'id' => (int) $row['assignedId'],
                    'username' => (string) ($row['assignedUsername'] ?? ''),
                    'fullName' => $this->buildUserLabel([
                        'firstname' => $row['assignedFirstname'] ?? '',
                        'lastname' => $row['assignedLastname'] ?? '',
                        'username' => $row['assignedUsername'] ?? '',
                    ]),
                ] : null,
                'actor' => null !== $row['actorId'] ? [
                    'id' => (int) $row['actorId'],
                    'username' => (string) ($row['actorUsername'] ?? ''),
                    'fullName' => $this->buildUserLabel([
                        'firstname' => $row['actorFirstname'] ?? '',
                        'lastname' => $row['actorLastname'] ?? '',
                        'username' => $row['actorUsername'] ?? '',
                    ]),
                ] : null,
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildUserLabel(array $row): string
    {
        $fullName = trim((string) ($row['firstname'] ?? '').' '.(string) ($row['lastname'] ?? ''));
        if ('' !== $fullName) {
            return $fullName;
        }

        $username = trim((string) ($row['username'] ?? ''));

        return '' !== $username ? $username : '-';
    }

    private function formatDate(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : null;
    }
}
