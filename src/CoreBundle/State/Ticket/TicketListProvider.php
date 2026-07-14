<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Ticket;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Ticket\TicketList;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\TicketPriority;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Entity\TicketStatus;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\TicketProjectHelper;
use Chamilo\CoreBundle\Service\Ticket\TicketWorkflowService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<TicketList>
 */
final readonly class TicketListProvider implements ProviderInterface
{
    private const DEFAULT_ITEMS_PER_PAGE = 20;
    private const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private AccessUrlHelper $accessUrlHelper,
        private TicketProjectHelper $ticketProjectHelper,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TicketList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new BadRequestHttpException('The authenticated user is required.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new BadRequestHttpException('The current access URL is required.');
        }

        $projects = $this->getProjects($accessUrl);
        $project = $this->resolveProject($projects, $request->query->getInt('projectId'));
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $result = new TicketList();
        $result->isAdmin = $isAdmin;
        $result->canCreate = $isAdmin
            || 'true' === $this->settingsManager->getSetting('ticket.ticket_allow_student_add');
        $result->csrfToken = $this->csrfTokenManager->getToken(TicketWorkflowService::CSRF_TOKEN_ID)->getValue();
        $result->projects = array_map(
            static fn (TicketProject $item): array => [
                'id' => (int) $item->getId(),
                'title' => $item->getTitle(),
                'description' => $item->getDescription(),
            ],
            $projects,
        );

        if (!$project instanceof TicketProject || null === $project->getId()) {
            return $result;
        }

        $projectId = (int) $project->getId();
        $userId = (int) $user->getId();
        $canViewAll = $this->ticketProjectHelper->userIsAllowInProject($projectId);
        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = min(
            self::MAX_ITEMS_PER_PAGE,
            max(1, $request->query->getInt('itemsPerPage', self::DEFAULT_ITEMS_PER_PAGE)),
        );

        $queryBuilder = $this->createTicketQueryBuilder($accessUrl, $project, $userId, $canViewAll);
        $this->applyFilters($queryBuilder, $request);

        $countQueryBuilder = clone $queryBuilder;
        $totalItems = (int) $countQueryBuilder
            ->resetDQLPart('orderBy')
            ->select('COUNT(DISTINCT ticket.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $this->applySorting($queryBuilder, $request);
        $rows = $queryBuilder
            ->select([
                'ticket.id AS id',
                'ticket.code AS code',
                'ticket.subject AS subject',
                'ticket.startDate AS createdAt',
                'ticket.lastEditDateTime AS updatedAt',
                'ticket.totalMessages AS totalMessages',
                'ticket.insertUserId AS creatorId',
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
            ])
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getArrayResult()
        ;

        $result->items = array_map($this->normalizeTicketRow(...), $rows);
        $result->totalItems = $totalItems;
        $result->page = $page;
        $result->itemsPerPage = $itemsPerPage;
        $result->projectId = $projectId;
        $result->canViewAll = $canViewAll;
        $result->categories = $this->getCategoryOptions($project);
        $result->statuses = $this->getStatusOptions($accessUrl);
        $result->priorities = $this->getPriorityOptions($accessUrl);
        $result->assignees = $this->getAssigneeOptions($accessUrl, $project);

        return $result;
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

        return array_values(array_filter(
            $projects,
            static fn (mixed $project): bool => $project instanceof TicketProject,
        ));
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
            foreach ($projects as $project) {
                if (1 === (int) $project->getId()) {
                    return $project;
                }
            }

            return $projects[0];
        }

        foreach ($projects as $project) {
            if ($requestedProjectId === (int) $project->getId()) {
                return $project;
            }
        }

        throw new BadRequestHttpException('The requested ticket project is not available for this access URL.');
    }

    private function createTicketQueryBuilder(
        AccessUrl $accessUrl,
        TicketProject $project,
        int $userId,
        bool $canViewAll,
    ): QueryBuilder {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(Ticket::class, 'ticket')
            ->innerJoin('ticket.project', 'project')
            ->innerJoin('ticket.category', 'category')
            ->innerJoin('ticket.priority', 'priority')
            ->innerJoin('ticket.status', 'status')
            ->leftJoin('ticket.assignedLastUser', 'assigned')
            ->leftJoin(User::class, 'creator', Join::WITH, 'creator.id = ticket.insertUserId')
            ->leftJoin('ticket.course', 'course')
            ->andWhere('IDENTITY(ticket.accessUrl) = :accessUrlId')
            ->andWhere('project.id = :projectId')
            ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
            ->setParameter('projectId', (int) $project->getId(), Types::INTEGER)
        ;

        if (!$canViewAll) {
            $queryBuilder
                ->andWhere('(ticket.insertUserId = :currentUserId OR assigned.id = :currentUserId)')
                ->setParameter('currentUserId', $userId, Types::INTEGER)
            ;
        }

        return $queryBuilder;
    }

    private function applyFilters(QueryBuilder $queryBuilder, Request $request): void
    {
        $keyword = trim((string) $request->query->get('keyword', ''));
        if ('' !== $keyword) {
            $keywordExpression = $queryBuilder->expr()->orX(
                'ticket.code LIKE :keyword',
                'ticket.subject LIKE :keyword',
                'ticket.message LIKE :keyword',
                'ticket.keyword LIKE :keyword',
                'ticket.personalEmail LIKE :keyword',
                'ticket.source LIKE :keyword',
                'category.title LIKE :keyword',
                'status.title LIKE :keyword',
                'priority.title LIKE :keyword',
            );

            if (ctype_digit($keyword)) {
                $keywordExpression->add('ticket.id = :keywordId');
                $queryBuilder->setParameter('keywordId', (int) $keyword, Types::INTEGER);
            }

            $queryBuilder
                ->andWhere($keywordExpression)
                ->setParameter('keyword', '%'.$keyword.'%', Types::STRING)
            ;
        }

        $filterMap = [
            'categoryId' => ['expression' => 'category.id = :categoryId', 'parameter' => 'categoryId'],
            'statusId' => ['expression' => 'status.id = :statusId', 'parameter' => 'statusId'],
            'priorityId' => ['expression' => 'priority.id = :priorityId', 'parameter' => 'priorityId'],
        ];

        foreach ($filterMap as $queryParameter => $filter) {
            $value = $request->query->getInt($queryParameter);
            if ($value <= 0) {
                continue;
            }

            $queryBuilder
                ->andWhere($filter['expression'])
                ->setParameter($filter['parameter'], $value, Types::INTEGER)
            ;
        }

        if ($request->query->has('assignedUserId')) {
            $assignedUserId = $request->query->getInt('assignedUserId');

            if (0 === $assignedUserId) {
                $queryBuilder->andWhere('assigned.id IS NULL');
            } elseif ($assignedUserId > 0) {
                $queryBuilder
                    ->andWhere('assigned.id = :assignedUserId')
                    ->setParameter('assignedUserId', $assignedUserId, Types::INTEGER)
                ;
            }
        }

        $course = trim((string) $request->query->get('course', ''));
        if ('' !== $course) {
            $queryBuilder
                ->andWhere('(course.title LIKE :course OR course.code LIKE :course OR course.visualCode LIKE :course)')
                ->setParameter('course', '%'.$course.'%', Types::STRING)
            ;
        }

        $startDate = $this->parseDate((string) $request->query->get('startDate', ''), false);
        if ($startDate instanceof DateTimeImmutable) {
            $queryBuilder
                ->andWhere('ticket.startDate >= :startDate')
                ->setParameter('startDate', $startDate, Types::DATETIME_IMMUTABLE)
            ;
        }

        $endDate = $this->parseDate((string) $request->query->get('endDate', ''), true);
        if ($endDate instanceof DateTimeImmutable) {
            $queryBuilder
                ->andWhere('ticket.startDate <= :endDate')
                ->setParameter('endDate', $endDate, Types::DATETIME_IMMUTABLE)
            ;
        }
    }

    private function applySorting(QueryBuilder $queryBuilder, Request $request): void
    {
        $sortMap = [
            'id' => 'ticket.id',
            'code' => 'ticket.code',
            'subject' => 'ticket.subject',
            'status' => 'status.title',
            'createdAt' => 'ticket.startDate',
            'updatedAt' => 'ticket.lastEditDateTime',
            'category' => 'category.title',
            'creator' => 'creator.lastname',
            'assignee' => 'assigned.lastname',
            'totalMessages' => 'ticket.totalMessages',
        ];

        $requestedSortField = (string) $request->query->get('sortField', 'id');
        $sortField = $sortMap[$requestedSortField] ?? $sortMap['id'];
        $sortDirection = 'asc' === strtolower((string) $request->query->get('sortDirection', 'desc'))
            ? 'ASC'
            : 'DESC';

        $queryBuilder->orderBy($sortField, $sortDirection);
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function getCategoryOptions(TicketProject $project): array
    {
        $categories = $this->entityManager->getRepository(TicketCategory::class)->findBy(
            ['project' => $project],
            ['title' => 'ASC'],
        );

        $result = [];
        foreach ($categories as $category) {
            if (!$category instanceof TicketCategory || null === $category->getId()) {
                continue;
            }

            $result[] = [
                'id' => (int) $category->getId(),
                'label' => (string) $category->getTitle(),
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
            if (!$status instanceof TicketStatus || null === $status->getId()) {
                continue;
            }

            $result[] = [
                'id' => (int) $status->getId(),
                'label' => (string) $status->getTitle(),
                'code' => (string) $status->getCode(),
            ];
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
            if (!$priority instanceof TicketPriority || null === $priority->getId()) {
                continue;
            }

            $result[] = [
                'id' => (int) $priority->getId(),
                'label' => (string) $priority->getTitle(),
                'code' => (string) $priority->getCode(),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{id: int, label: string, username: string}>
     */
    private function getAssigneeOptions(AccessUrl $accessUrl, TicketProject $project): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select([
                'DISTINCT assigned.id AS id',
                'assigned.username AS username',
                'assigned.firstname AS firstname',
                'assigned.lastname AS lastname',
            ])
            ->from(Ticket::class, 'ticket')
            ->innerJoin('ticket.project', 'project')
            ->innerJoin('ticket.assignedLastUser', 'assigned')
            ->andWhere('IDENTITY(ticket.accessUrl) = :accessUrlId')
            ->andWhere('project.id = :projectId')
            ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
            ->setParameter('projectId', (int) $project->getId(), Types::INTEGER)
            ->orderBy('assigned.lastname', 'ASC')
            ->addOrderBy('assigned.firstname', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) $row['id'],
                'label' => $this->buildUserLabel($row),
                'username' => (string) ($row['username'] ?? ''),
            ];
        }

        return $result;
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
            'createdAt' => $this->formatDate($row['createdAt'] ?? null),
            'updatedAt' => $this->formatDate($row['updatedAt'] ?? null),
            'totalMessages' => (int) $row['totalMessages'],
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
        ];
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

    private function parseDate(string $value, bool $endOfDay): ?DateTimeImmutable
    {
        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        $suffix = $endOfDay ? ' 23:59:59' : ' 00:00:00';
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value.$suffix);

        return $date instanceof DateTimeImmutable ? $date : null;
    }

    private function formatDate(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : null;
    }
}
