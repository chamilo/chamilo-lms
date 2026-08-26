<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Mcp;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\TicketMessage;
use Chamilo\CoreBundle\Entity\TicketMessageAttachment;
use Chamilo\CoreBundle\Entity\TicketPriority;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Entity\TicketStatus;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Repository\Node\TicketMessageAttachmentRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use const COURSEMANAGERLOWSECURITY;
use const DATE_ATOM;

/**
 * Shared authorization and read logic for the ticket MCP tools. The write actions
 * (update/reply/close) are otherwise delegated straight to TicketWorkflowService,
 * which already implements them for the legacy ticket API.
 */
final readonly class McpTicketService
{
    private const int MAX_ITEMS_PER_PAGE = 100;

    /**
     * @var array<string, string>
     */
    private const array SORT_FIELDS = [
        'id' => 'ticket.id',
        'code' => 'ticket.code',
        'subject' => 'ticket.subject',
        'status' => 'status.title',
        'priority' => 'priority.title',
        'createdAt' => 'ticket.startDate',
        'updatedAt' => 'ticket.lastEditDateTime',
    ];

    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private AccessUrlScopeHelper $accessUrlScopeHelper,
        private EntityManagerInterface $entityManager,
        private TicketMessageAttachmentRepository $attachmentRepository,
    ) {}

    /**
     * Every ticket MCP tool must call this first: only administrators who manage the
     * portal (access URL) the current MCP connection resolves to may read or change
     * its tickets.
     */
    public function assertPortalAdmin(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('An authenticated Chamilo user is required.');
        }

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only administrators can use the ticket tools.');
        }

        $accessUrl = $this->currentAccessUrl();

        if (!$this->accessUrlScopeHelper->isUrlManaged($user, (int) $accessUrl->getId())) {
            throw new AccessDeniedException('You do not administer the portal this ticket belongs to.');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    public function listTickets(
        ?int $projectId,
        ?int $statusId,
        ?int $priorityId,
        ?int $categoryId,
        ?int $assignedUserId,
        ?string $keyword,
        int $page,
        int $itemsPerPage,
        string $sortField,
        string $sortDirection,
    ): array {
        $accessUrl = $this->currentAccessUrl();
        $projects = $this->getProjects($accessUrl);
        $project = $this->resolveProject($projects, $projectId ?? 0);

        $result = [
            'projects' => array_map(
                static fn (TicketProject $item): array => ['id' => (int) $item->getId(), 'title' => $item->getTitle()],
                $projects,
            ),
            'project_id' => $project instanceof TicketProject ? (int) $project->getId() : 0,
            'total_items' => 0,
            'page' => max(1, $page),
            'items_per_page' => min(self::MAX_ITEMS_PER_PAGE, max(1, $itemsPerPage)),
            'tickets' => [],
            'categories' => [],
            'statuses' => [],
            'priorities' => [],
        ];

        if (!$project instanceof TicketProject) {
            return $result;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(Ticket::class, 'ticket')
            ->innerJoin('ticket.project', 'project')
            ->innerJoin('ticket.category', 'category')
            ->innerJoin('ticket.priority', 'priority')
            ->innerJoin('ticket.status', 'status')
            ->leftJoin('ticket.assignedLastUser', 'assigned')
            ->leftJoin(User::class, 'creator', Join::WITH, 'creator.id = ticket.insertUserId')
            ->andWhere('IDENTITY(ticket.accessUrl) = :accessUrlId')
            ->andWhere('project.id = :projectId')
            ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
            ->setParameter('projectId', (int) $project->getId(), Types::INTEGER)
        ;

        $keyword = trim((string) $keyword);
        if ('' !== $keyword) {
            $expression = $queryBuilder->expr()->orX(
                'ticket.code LIKE :keyword',
                'ticket.subject LIKE :keyword',
                'ticket.message LIKE :keyword',
            );
            if (ctype_digit($keyword)) {
                $expression->add('ticket.id = :keywordId');
                $queryBuilder->setParameter('keywordId', (int) $keyword, Types::INTEGER);
            }
            $queryBuilder->andWhere($expression)->setParameter('keyword', '%'.$keyword.'%', Types::STRING);
        }

        if (null !== $categoryId && $categoryId > 0) {
            $queryBuilder->andWhere('category.id = :categoryId')->setParameter('categoryId', $categoryId, Types::INTEGER);
        }
        if (null !== $statusId && $statusId > 0) {
            $queryBuilder->andWhere('status.id = :statusId')->setParameter('statusId', $statusId, Types::INTEGER);
        }
        if (null !== $priorityId && $priorityId > 0) {
            $queryBuilder->andWhere('priority.id = :priorityId')->setParameter('priorityId', $priorityId, Types::INTEGER);
        }
        if (null !== $assignedUserId) {
            if (0 === $assignedUserId) {
                $queryBuilder->andWhere('assigned.id IS NULL');
            } else {
                $queryBuilder
                    ->andWhere('assigned.id = :assignedUserId')
                    ->setParameter('assignedUserId', $assignedUserId, Types::INTEGER)
                ;
            }
        }

        $countQueryBuilder = clone $queryBuilder;
        $totalItems = (int) $countQueryBuilder
            ->select('COUNT(DISTINCT ticket.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $page = max(1, $page);
        $itemsPerPage = min(self::MAX_ITEMS_PER_PAGE, max(1, $itemsPerPage));
        $orderBy = self::SORT_FIELDS[$sortField] ?? self::SORT_FIELDS['id'];
        $direction = 'asc' === strtolower($sortDirection) ? 'ASC' : 'DESC';

        $rows = $queryBuilder
            ->select([
                'ticket.id AS id',
                'ticket.code AS code',
                'ticket.subject AS subject',
                'ticket.startDate AS createdAt',
                'ticket.lastEditDateTime AS updatedAt',
                'ticket.totalMessages AS totalMessages',
                'category.id AS categoryId',
                'category.title AS categoryTitle',
                'priority.id AS priorityId',
                'priority.title AS priorityTitle',
                'status.id AS statusId',
                'status.title AS statusTitle',
                'creator.id AS creatorId',
                'creator.username AS creatorUsername',
                'creator.firstname AS creatorFirstname',
                'creator.lastname AS creatorLastname',
                'assigned.id AS assignedUserId',
                'assigned.username AS assignedUsername',
                'assigned.firstname AS assignedFirstname',
                'assigned.lastname AS assignedLastname',
            ])
            ->orderBy($orderBy, $direction)
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getArrayResult()
        ;

        $result['total_items'] = $totalItems;
        $result['page'] = $page;
        $result['items_per_page'] = $itemsPerPage;
        $result['tickets'] = array_map($this->normalizeTicketRow(...), $rows);
        $result['categories'] = $this->getCategoryOptions($project);
        $result['statuses'] = $this->getScopedOptions(TicketStatus::class, $accessUrl);
        $result['priorities'] = $this->getScopedOptions(TicketPriority::class, $accessUrl);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTicketDetail(Ticket $ticket): array
    {
        $creator = $this->entityManager->getRepository(User::class)->find($ticket->getInsertUserId());
        $assignee = $ticket->getAssignedLastUser();
        $course = $ticket->getCourse();
        $session = $ticket->getSession();

        return [
            'id' => (int) $ticket->getId(),
            'code' => $ticket->getCode(),
            'subject' => $ticket->getSubject(),
            'message_html' => (string) \Security::remove_XSS((string) $ticket->getMessage(), COURSEMANAGERLOWSECURITY),
            'created_at' => $this->formatDate($ticket->getInsertDateTime()),
            'updated_at' => $this->formatDate($ticket->getLastEditDateTime()),
            'closed_at' => $this->formatDate($ticket->getEndDate()),
            'total_messages' => $ticket->getTotalMessages(),
            'project' => ['id' => (int) $ticket->getProject()->getId(), 'title' => $ticket->getProject()->getTitle()],
            'category' => ['id' => (int) $ticket->getCategory()->getId(), 'title' => $ticket->getCategory()->getTitle()],
            'priority' => [
                'id' => (int) $ticket->getPriority()->getId(),
                'title' => $ticket->getPriority()->getTitle(),
                'code' => $ticket->getPriority()->getCode(),
            ],
            'status' => [
                'id' => (int) $ticket->getStatus()->getId(),
                'title' => $ticket->getStatus()->getTitle(),
                'code' => $ticket->getStatus()->getCode(),
            ],
            'creator' => $creator instanceof User ? $this->normalizeUser($creator) : null,
            'assignee' => $assignee instanceof User ? $this->normalizeUser($assignee) : null,
            'course' => $course instanceof Course ? [
                'id' => (int) $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
            ] : null,
            'session' => $session instanceof Session ? [
                'id' => (int) $session->getId(),
                'title' => $session->getTitle(),
            ] : null,
            'messages' => $this->getMessages($ticket),
        ];
    }

    private function currentAccessUrl(): AccessUrl
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new RuntimeException('The current Chamilo portal could not be resolved.');
        }

        return $accessUrl;
    }

    /**
     * @return array<int, TicketProject>
     */
    private function getProjects(AccessUrl $accessUrl): array
    {
        $repository = $this->entityManager->getRepository(TicketProject::class);
        $projects = $repository->findBy(['accessUrl' => $accessUrl], ['title' => 'ASC']);
        if ([] === $projects) {
            $projects = $repository->findBy(['accessUrl' => null], ['title' => 'ASC']);
        }

        return array_values(array_filter($projects, static fn (mixed $item): bool => $item instanceof TicketProject));
    }

    /**
     * @param array<int, TicketProject> $projects
     */
    private function resolveProject(array $projects, int $requestedProjectId): ?TicketProject
    {
        if ([] === $projects) {
            return null;
        }
        if ($requestedProjectId <= 0) {
            return $projects[0];
        }
        foreach ($projects as $project) {
            if ($requestedProjectId === (int) $project->getId()) {
                return $project;
            }
        }

        throw new InvalidArgumentException('The requested ticket project is not available for this portal.');
    }

    /**
     * @return array<int, array{id: int, title: string}>
     */
    private function getCategoryOptions(TicketProject $project): array
    {
        $categories = $this->entityManager
            ->getRepository(TicketCategory::class)
            ->findBy(['project' => $project], ['title' => 'ASC'])
        ;

        $result = [];
        foreach ($categories as $category) {
            if ($category instanceof TicketCategory && null !== $category->getId()) {
                $result[] = ['id' => (int) $category->getId(), 'title' => $category->getTitle()];
            }
        }

        return $result;
    }

    /**
     * @param class-string $class
     *
     * @return array<int, array{id: int, title: string, code: string}>
     */
    private function getScopedOptions(string $class, AccessUrl $accessUrl): array
    {
        $repository = $this->entityManager->getRepository($class);
        $items = $repository->findBy(['accessUrl' => $accessUrl], ['title' => 'ASC']);
        if ([] === $items) {
            $items = $repository->findBy(['accessUrl' => null], ['title' => 'ASC']);
        }

        $result = [];
        foreach ($items as $item) {
            if (($item instanceof TicketStatus || $item instanceof TicketPriority) && null !== $item->getId()) {
                $result[] = ['id' => (int) $item->getId(), 'title' => $item->getTitle(), 'code' => $item->getCode()];
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMessages(Ticket $ticket): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select([
                'message.id AS id',
                'message.subject AS subject',
                'message.message AS messageContent',
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
            ->setParameter('ticketId', (int) $ticket->getId(), Types::INTEGER)
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ->orderBy('message.insertDateTime', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $attachmentsByMessage = [];
        foreach ($this->attachmentRepository->findBy(['ticket' => $ticket]) as $attachment) {
            if (!$attachment instanceof TicketMessageAttachment || null === $attachment->getId()) {
                continue;
            }
            $attachmentsByMessage[(int) $attachment->getMessage()->getId()][] = [
                'id' => (int) $attachment->getId(),
                'filename' => $attachment->getFilename(),
                'size' => (int) $attachment->getSize(),
                'url' => $this->attachmentRepository->getResourceFileDownloadUrl($attachment),
            ];
        }

        $messages = [];
        foreach ($rows as $index => $row) {
            $messageId = (int) $row['id'];
            $messages[] = [
                'id' => $messageId,
                'number' => $index + 1,
                'subject' => (string) ($row['subject'] ?? ''),
                'message_html' => (string) \Security::remove_XSS(
                    (string) ($row['messageContent'] ?? ''),
                    COURSEMANAGERLOWSECURITY,
                ),
                'created_at' => $this->formatDate($row['createdAt'] ?? null),
                'author' => [
                    'id' => (int) $row['authorId'],
                    'username' => (string) $row['authorUsername'],
                    'full_name' => $this->buildUserLabel(
                        (string) ($row['authorFirstname'] ?? ''),
                        (string) ($row['authorLastname'] ?? ''),
                        (string) $row['authorUsername'],
                    ),
                ],
                'attachments' => $attachmentsByMessage[$messageId] ?? [],
            ];
        }

        return $messages;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeTicketRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'subject' => (string) $row['subject'],
            'created_at' => $this->formatDate($row['createdAt'] ?? null),
            'updated_at' => $this->formatDate($row['updatedAt'] ?? null),
            'total_messages' => (int) $row['totalMessages'],
            'category' => ['id' => (int) $row['categoryId'], 'title' => (string) $row['categoryTitle']],
            'priority' => ['id' => (int) $row['priorityId'], 'title' => (string) $row['priorityTitle']],
            'status' => ['id' => (int) $row['statusId'], 'title' => (string) $row['statusTitle']],
            'creator' => null !== $row['creatorId'] ? [
                'id' => (int) $row['creatorId'],
                'username' => (string) ($row['creatorUsername'] ?? ''),
                'full_name' => $this->buildUserLabel(
                    (string) ($row['creatorFirstname'] ?? ''),
                    (string) ($row['creatorLastname'] ?? ''),
                    (string) ($row['creatorUsername'] ?? ''),
                ),
            ] : null,
            'assignee' => null !== $row['assignedUserId'] ? [
                'id' => (int) $row['assignedUserId'],
                'username' => (string) ($row['assignedUsername'] ?? ''),
                'full_name' => $this->buildUserLabel(
                    (string) ($row['assignedFirstname'] ?? ''),
                    (string) ($row['assignedLastname'] ?? ''),
                    (string) ($row['assignedUsername'] ?? ''),
                ),
            ] : null,
        ];
    }

    /**
     * @return array{id: int, username: string, full_name: string}
     */
    private function normalizeUser(User $user): array
    {
        return [
            'id' => (int) $user->getId(),
            'username' => $user->getUsername(),
            'full_name' => $this->buildUserLabel($user->getFirstname(), $user->getLastname(), $user->getUsername()),
        ];
    }

    private function buildUserLabel(string $firstname, string $lastname, string $username): string
    {
        $fullName = trim($firstname.' '.$lastname);

        return '' !== $fullName ? $fullName : $username;
    }

    private function formatDate(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : null;
    }
}
