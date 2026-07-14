<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Ticket;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\TicketCategoryRelUser;
use Chamilo\CoreBundle\Entity\TicketPriority;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Entity\TicketStatus;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use URLify;

use const COURSEMANAGERLOWSECURITY;
use const ENT_HTML5;
use const ENT_QUOTES;

final readonly class TicketAdminService
{
    private const DEFAULT_PRIORITY_CODES = ['1', '2', '3'];
    private const DEFAULT_STATUS_CODES = ['1', '2', '3', '4', '5'];
    private const STATUS_CLOSED = 4;
    private const STATUS_FORWARDED = 5;
    private const STATUS_NEW = 1;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(int $requestedProjectId): array
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $projects = $this->getProjects($accessUrl);
        $project = $this->resolveProject($projects, $requestedProjectId);

        return [
            'projects' => array_map(
                fn (TicketProject $item): array => $this->normalizeProject($item, $accessUrl),
                $projects,
            ),
            'projectId' => $project instanceof TicketProject ? (int) $project->getId() : 0,
            'categories' => $project instanceof TicketProject ? $this->getCategories($project, $accessUrl) : [],
            'statuses' => $this->getStatuses($accessUrl),
            'priorities' => $this->getPriorities($accessUrl),
            'allowCategoryEdition' => 'true' === $this->settingsManager->getSetting(
                'ticket.ticket_allow_category_edition',
            ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createProject(User $actor, array $data): TicketProject
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $project = (new TicketProject())
            ->setTitle($this->requiredTitle($data))
            ->setDescription($this->sanitizeDescription($data))
            ->setOtherArea(0)
            ->setInsertUserId((int) $actor->getId())
            ->setInsertDateTime(new DateTime('now'))
            ->setAccessUrl($this->getConfigurationAccessUrl($accessUrl))
        ;
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateProject(int $id, User $actor, array $data): TicketProject
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $project = $this->getEditableProject($id, $accessUrl);
        $project
            ->setTitle($this->requiredTitle($data))
            ->setDescription($this->sanitizeDescription($data))
            ->setLastEditUserId((int) $actor->getId())
            ->setLastEditDateTime(new DateTime('now'))
        ;
        $this->entityManager->flush();

        return $project;
    }

    public function deleteProject(int $id): void
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $project = $this->getEditableProject($id, $accessUrl);
        $ticketCount = $this->entityManager->getRepository(Ticket::class)->count(['project' => $project]);
        $categoryCount = $this->entityManager->getRepository(TicketCategory::class)->count(['project' => $project]);
        if ($ticketCount > 0 || $categoryCount > 0) {
            throw new BadRequestHttpException('This item is related to other tickets.');
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createCategory(int $projectId, User $actor, array $data): TicketCategory
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $project = $this->getEditableProject($projectId, $accessUrl);
        $category = (new TicketCategory())
            ->setTitle($this->requiredTitle($data))
            ->setDescription($this->sanitizeDescription($data))
            ->setTotalTickets(0)
            ->setCourseRequired(false)
            ->setProject($project)
            ->setInsertUserId((int) $actor->getId())
            ->setInsertDateTime(new DateTime('now'))
        ;
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCategory(int $id, User $actor, array $data): TicketCategory
    {
        $this->assertCategoryEditionAllowed();
        $accessUrl = $this->getCurrentAccessUrl();
        $category = $this->getEditableCategory($id, $accessUrl);
        $category
            ->setTitle($this->requiredTitle($data))
            ->setDescription($this->sanitizeDescription($data))
            ->setLastEditUserId((int) $actor->getId())
            ->setLastEditDateTime(new DateTime('now'))
        ;
        $this->entityManager->flush();

        return $category;
    }

    public function deleteCategory(int $id): void
    {
        $this->assertCategoryEditionAllowed();
        $accessUrl = $this->getCurrentAccessUrl();
        $category = $this->getEditableCategory($id, $accessUrl);
        if ($this->entityManager->getRepository(Ticket::class)->count(['category' => $category]) > 0) {
            throw new BadRequestHttpException('This item is related to other tickets.');
        }

        $relations = $this->entityManager
            ->getRepository(TicketCategoryRelUser::class)
            ->findBy(['category' => $category])
        ;
        foreach ($relations as $relation) {
            $this->entityManager->remove($relation);
        }
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    /**
     * @param array<int, int> $userIds
     */
    public function replaceCategoryUsers(int $id, array $userIds): void
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $category = $this->getEditableCategory($id, $accessUrl);
        $userIds = array_values(
            array_unique(
                array_filter(array_map('intval', $userIds), static fn (int $value): bool => $value > 0),
            ),
        );
        $users = [];

        if ([] !== $userIds) {
            $users = $this->entityManager->createQueryBuilder()
                ->select('DISTINCT user')
                ->from(User::class, 'user')
                ->innerJoin('user.portals', 'portal')
                ->andWhere('IDENTITY(portal.url) = :accessUrlId')
                ->andWhere('user.id IN (:userIds)')
                ->andWhere('user.active = :active')
                ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
                ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
                ->setParameter('active', User::ACTIVE, Types::INTEGER)
                ->getQuery()
                ->getResult()
            ;
            if (\count($users) !== \count($userIds)) {
                throw new BadRequestHttpException('One or more selected users are invalid.');
            }
        }

        $relations = $this->entityManager
            ->getRepository(TicketCategoryRelUser::class)
            ->findBy(['category' => $category])
        ;
        foreach ($relations as $relation) {
            $this->entityManager->remove($relation);
        }
        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }
            $relation = (new TicketCategoryRelUser())
                ->setCategory($category)
                ->setUser($user)
            ;
            $this->entityManager->persist($relation);
        }
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createStatus(array $data): TicketStatus
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $status = (new TicketStatus())
            ->setCode(URLify::filter($this->requiredTitle($data)))
            ->setTitle($this->requiredTitle($data))
            ->setDescription($this->sanitizeDescription($data))
            ->setAccessUrl($this->getConfigurationAccessUrl($accessUrl))
        ;
        $this->entityManager->persist($status);
        $this->entityManager->flush();

        return $status;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateStatus(int $id, array $data): TicketStatus
    {
        $status = $this->getEditableStatus($id, $this->getCurrentAccessUrl());
        $status
            ->setTitle($this->requiredTitle($data))
            ->setDescription($this->sanitizeDescription($data))
        ;
        $this->entityManager->flush();

        return $status;
    }

    public function deleteStatus(int $id): void
    {
        $status = $this->getEditableStatus($id, $this->getCurrentAccessUrl());
        if (\in_array((string) $status->getCode(), self::DEFAULT_STATUS_CODES, true)) {
            throw new BadRequestHttpException('Default statuses cannot be deleted.');
        }
        if ($this->entityManager->getRepository(Ticket::class)->count(['status' => $status]) > 0) {
            throw new BadRequestHttpException('This item is related to other tickets.');
        }
        $this->entityManager->remove($status);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createPriority(User $actor, array $data): TicketPriority
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $title = $this->requiredTitle($data);
        $priority = (new TicketPriority())
            ->setCode(URLify::filter($title))
            ->setTitle($title)
            ->setDescription($this->sanitizeDescription($data))
            ->setColor('')
            ->setUrgency('')
            ->setInsertUserId((int) $actor->getId())
            ->setInsertDateTime(new DateTime('now'))
            ->setAccessUrl($this->getConfigurationAccessUrl($accessUrl))
        ;
        $this->entityManager->persist($priority);
        $this->entityManager->flush();

        return $priority;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updatePriority(int $id, User $actor, array $data): TicketPriority
    {
        $priority = $this->getEditablePriority($id, $this->getCurrentAccessUrl());
        $priority
            ->setTitle($this->requiredTitle($data))
            ->setDescription($this->sanitizeDescription($data))
            ->setLastEditUserId((int) $actor->getId())
            ->setLastEditDateTime(new DateTime('now'))
        ;
        $this->entityManager->flush();

        return $priority;
    }

    public function deletePriority(int $id): void
    {
        $priority = $this->getEditablePriority($id, $this->getCurrentAccessUrl());
        if (\in_array((string) $priority->getCode(), self::DEFAULT_PRIORITY_CODES, true)) {
            throw new BadRequestHttpException('Default priorities cannot be deleted.');
        }
        if ($this->entityManager->getRepository(Ticket::class)->count(['priority' => $priority]) > 0) {
            throw new BadRequestHttpException('This item is related to other tickets.');
        }
        $this->entityManager->remove($priority);
        $this->entityManager->flush();
    }

    public function closeOldTickets(User $actor): int
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $closedStatus = $this->getAvailableStatus(self::STATUS_CLOSED, $accessUrl);
        $cutoff = new DateTimeImmutable('-7 days');
        $tickets = $this->entityManager->createQueryBuilder()
            ->select('ticket')
            ->from(Ticket::class, 'ticket')
            ->andWhere('IDENTITY(ticket.accessUrl) = :accessUrlId')
            ->andWhere('ticket.lastEditDateTime < :cutoff')
            ->andWhere('IDENTITY(ticket.status) NOT IN (:excludedStatuses)')
            ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
            ->setParameter('cutoff', $cutoff, Types::DATETIME_IMMUTABLE)
            ->setParameter(
                'excludedStatuses',
                [self::STATUS_NEW, self::STATUS_CLOSED, self::STATUS_FORWARDED],
                ArrayParameterType::INTEGER,
            )
            ->getQuery()
            ->getResult()
        ;
        $now = new DateTime('now');
        $count = 0;
        foreach ($tickets as $ticket) {
            if (!$ticket instanceof Ticket) {
                continue;
            }
            $ticket
                ->setStatus($closedStatus)
                ->setLastEditUserId((int) $actor->getId())
                ->setLastEditDateTime($now)
                ->setEndDate($now)
            ;
            ++$count;
        }
        $this->entityManager->flush();

        return $count;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<int, array<int, string>>
     */
    public function getExportRows(array $filters): array
    {
        $accessUrl = $this->getCurrentAccessUrl();
        $projectId = max(0, (int) ($filters['projectId'] ?? 0));
        if ($projectId <= 0) {
            throw new BadRequestHttpException('A ticket project is required.');
        }
        $project = $this->getAvailableProject($projectId, $accessUrl);
        $queryBuilder = $this->createExportQueryBuilder($accessUrl, $project);
        $this->applyExportFilters($queryBuilder, $filters);
        $rows = $queryBuilder
            ->select([
                'ticket.code AS code',
                'ticket.startDate AS createdAt',
                'ticket.lastEditDateTime AS updatedAt',
                'category.title AS category',
                'creator.firstname AS creatorFirstname',
                'creator.lastname AS creatorLastname',
                'creator.username AS creatorUsername',
                'ticket.subject AS subject',
                'assigned.firstname AS assignedFirstname',
                'assigned.lastname AS assignedLastname',
                'assigned.username AS assignedUsername',
                'status.title AS status',
                'ticket.message AS description',
            ])
            ->orderBy('ticket.id', 'DESC')
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [[
            '#',
            get_lang('Date'),
            get_lang('Last update'),
            get_lang('Category'),
            get_lang('User'),
            get_lang('Title'),
            get_lang('Assigned to'),
            get_lang('Status'),
            get_lang('Description'),
        ]];
        foreach ($rows as $row) {
            $result[] = [
                $this->safeSpreadsheetValue((string) ($row['code'] ?? '')),
                $this->formatDate($row['createdAt'] ?? null),
                $this->formatDate($row['updatedAt'] ?? null),
                $this->safeSpreadsheetValue((string) ($row['category'] ?? '')),
                $this->safeSpreadsheetValue($this->buildUserLabel($row, 'creator')),
                $this->safeSpreadsheetValue((string) ($row['subject'] ?? '')),
                $this->safeSpreadsheetValue($this->buildUserLabel($row, 'assigned')),
                $this->safeSpreadsheetValue((string) ($row['status'] ?? '')),
                $this->safeSpreadsheetValue(
                    html_entity_decode(
                        trim(strip_tags((string) ($row['description'] ?? ''))),
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8',
                    ),
                ),
            ];
        }

        return $result;
    }

    private function getCurrentAccessUrl(): AccessUrl
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new BadRequestHttpException('The current access URL is required.');
        }

        return $accessUrl;
    }

    /**
     * @return array<int, TicketProject>
     */
    private function getProjects(AccessUrl $accessUrl): array
    {
        $projects = $this->entityManager
            ->getRepository(TicketProject::class)
            ->findBy(['accessUrl' => $accessUrl], ['title' => 'ASC'])
        ;
        if ([] === $projects) {
            $projects = $this->entityManager
                ->getRepository(TicketProject::class)
                ->findBy(['accessUrl' => null], ['title' => 'ASC'])
            ;
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

        throw new BadRequestHttpException('The selected ticket project is invalid.');
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeProject(TicketProject $project, AccessUrl $accessUrl): array
    {
        return [
            'id' => (int) $project->getId(),
            'title' => $project->getTitle(),
            'description' => $project->getDescription(),
            'ticketCount' => $this->entityManager->getRepository(Ticket::class)->count(['project' => $project]),
            'categoryCount' => $this->entityManager
                ->getRepository(TicketCategory::class)
                ->count(['project' => $project]),
            'editable' => $this->isEditableAccessUrl($project->getAccessUrl(), $accessUrl),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(TicketProject $project, AccessUrl $accessUrl): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select([
                'category.id AS categoryId',
                'user.id AS userId',
                'user.username AS username',
                'user.firstname AS firstname',
                'user.lastname AS lastname',
            ])
            ->from(TicketCategoryRelUser::class, 'relation')
            ->innerJoin('relation.category', 'category')
            ->innerJoin('relation.user', 'user')
            ->andWhere('IDENTITY(category.project) = :projectId')
            ->setParameter('projectId', (int) $project->getId(), Types::INTEGER)
            ->orderBy('user.lastname', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
        $usersByCategory = [];
        foreach ($relations as $row) {
            $categoryId = (int) $row['categoryId'];
            $usersByCategory[$categoryId][] = [
                'id' => (int) $row['userId'],
                'username' => (string) $row['username'],
                'label' => $this->buildUserLabel($row),
            ];
        }
        $editable = $this->isEditableAccessUrl($project->getAccessUrl(), $accessUrl);
        $result = [];
        $categories = $this->entityManager
            ->getRepository(TicketCategory::class)
            ->findBy(['project' => $project], ['title' => 'ASC'])
        ;
        foreach ($categories as $category) {
            if (!$category instanceof TicketCategory || null === $category->getId()) {
                continue;
            }
            $result[] = [
                'id' => (int) $category->getId(),
                'projectId' => (int) $project->getId(),
                'title' => (string) $category->getTitle(),
                'description' => $category->getDescription(),
                'totalTickets' => $this->entityManager->getRepository(Ticket::class)->count(['category' => $category]),
                'courseRequired' => $category->isCourseRequired(),
                'users' => $usersByCategory[(int) $category->getId()] ?? [],
                'editable' => $editable,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getStatuses(AccessUrl $accessUrl): array
    {
        $items = $this->getScopedItems(TicketStatus::class, $accessUrl);
        $result = [];
        foreach ($items as $status) {
            if (!$status instanceof TicketStatus || null === $status->getId()) {
                continue;
            }
            $result[] = [
                'id' => (int) $status->getId(),
                'code' => (string) $status->getCode(),
                'title' => (string) $status->getTitle(),
                'description' => $status->getDescription(),
                'ticketCount' => $this->entityManager->getRepository(Ticket::class)->count(['status' => $status]),
                'protected' => \in_array((string) $status->getCode(), self::DEFAULT_STATUS_CODES, true),
                'editable' => $this->isEditableAccessUrl($status->getAccessUrl(), $accessUrl),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPriorities(AccessUrl $accessUrl): array
    {
        $items = $this->getScopedItems(TicketPriority::class, $accessUrl);
        $result = [];
        foreach ($items as $priority) {
            if (!$priority instanceof TicketPriority || null === $priority->getId()) {
                continue;
            }
            $result[] = [
                'id' => (int) $priority->getId(),
                'code' => (string) $priority->getCode(),
                'title' => (string) $priority->getTitle(),
                'description' => $priority->getDescription(),
                'ticketCount' => $this->entityManager->getRepository(Ticket::class)->count(['priority' => $priority]),
                'protected' => \in_array((string) $priority->getCode(), self::DEFAULT_PRIORITY_CODES, true),
                'editable' => $this->isEditableAccessUrl($priority->getAccessUrl(), $accessUrl),
            ];
        }

        return $result;
    }

    /**
     * @param class-string $class
     *
     * @return array<int, object>
     */
    private function getScopedItems(string $class, AccessUrl $accessUrl): array
    {
        $items = $this->entityManager->getRepository($class)->findBy(['accessUrl' => $accessUrl], ['title' => 'ASC']);
        if ([] === $items) {
            $items = $this->entityManager->getRepository($class)->findBy(['accessUrl' => null], ['title' => 'ASC']);
        }

        return $items;
    }

    private function getEditableProject(int $id, AccessUrl $accessUrl): TicketProject
    {
        $project = $this->getAvailableProject($id, $accessUrl);
        if (!$this->isEditableAccessUrl($project->getAccessUrl(), $accessUrl)) {
            throw new BadRequestHttpException('This shared configuration cannot be modified from the current access URL.');
        }

        return $project;
    }

    private function getAvailableProject(int $id, AccessUrl $accessUrl): TicketProject
    {
        $project = $this->entityManager->getRepository(TicketProject::class)->find($id);
        if (!$project instanceof TicketProject || !$this->isAvailableAccessUrl($project->getAccessUrl(), $accessUrl)) {
            throw new NotFoundHttpException('The selected ticket project was not found.');
        }

        return $project;
    }

    private function getEditableCategory(int $id, AccessUrl $accessUrl): TicketCategory
    {
        $category = $this->entityManager->getRepository(TicketCategory::class)->find($id);
        if (!$category instanceof TicketCategory
            || !$this->isEditableAccessUrl($category->getProject()->getAccessUrl(), $accessUrl)
        ) {
            throw new NotFoundHttpException('The selected ticket category was not found.');
        }

        return $category;
    }

    private function getEditableStatus(int $id, AccessUrl $accessUrl): TicketStatus
    {
        $status = $this->getAvailableStatus($id, $accessUrl);
        if (!$this->isEditableAccessUrl($status->getAccessUrl(), $accessUrl)) {
            throw new BadRequestHttpException('This shared configuration cannot be modified from the current access URL.');
        }

        return $status;
    }

    private function getAvailableStatus(int $id, AccessUrl $accessUrl): TicketStatus
    {
        $status = $this->entityManager->getRepository(TicketStatus::class)->find($id);
        if (!$status instanceof TicketStatus || !$this->isAvailableAccessUrl($status->getAccessUrl(), $accessUrl)) {
            throw new NotFoundHttpException('The selected ticket status was not found.');
        }

        return $status;
    }

    private function getEditablePriority(int $id, AccessUrl $accessUrl): TicketPriority
    {
        $priority = $this->entityManager->getRepository(TicketPriority::class)->find($id);
        if (!$priority instanceof TicketPriority
            || !$this->isEditableAccessUrl($priority->getAccessUrl(), $accessUrl)
        ) {
            throw new NotFoundHttpException('The selected ticket priority was not found.');
        }

        return $priority;
    }

    private function isAvailableAccessUrl(?AccessUrl $itemAccessUrl, AccessUrl $current): bool
    {
        return null === $itemAccessUrl || $itemAccessUrl->getId() === $current->getId();
    }

    private function getConfigurationAccessUrl(AccessUrl $current): ?AccessUrl
    {
        return 1 === (int) $current->getId() ? null : $current;
    }

    private function isEditableAccessUrl(?AccessUrl $itemAccessUrl, AccessUrl $current): bool
    {
        if ($itemAccessUrl instanceof AccessUrl) {
            return $itemAccessUrl->getId() === $current->getId();
        }

        return 1 === (int) $current->getId();
    }

    private function assertCategoryEditionAllowed(): void
    {
        if ('true' !== $this->settingsManager->getSetting('ticket.ticket_allow_category_edition')) {
            throw new BadRequestHttpException('Ticket category edition is disabled by the platform setting.');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requiredTitle(array $data): string
    {
        $title = mb_substr(trim((string) ($data['title'] ?? '')), 0, 255);
        if ('' === $title) {
            throw new BadRequestHttpException('The title is required.');
        }

        return $title;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function sanitizeDescription(array $data): string
    {
        return (string) Security::remove_XSS((string) ($data['description'] ?? ''), COURSEMANAGERLOWSECURITY);
    }

    private function createExportQueryBuilder(AccessUrl $accessUrl, TicketProject $project): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
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
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyExportFilters(QueryBuilder $queryBuilder, array $filters): void
    {
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ('' !== $keyword) {
            $queryBuilder
                ->andWhere(
                    '(ticket.code LIKE :keyword OR ticket.subject LIKE :keyword OR ticket.message LIKE :keyword)',
                )
                ->setParameter('keyword', '%'.$keyword.'%', Types::STRING)
            ;
        }
        $integerFilters = [
            'categoryId' => 'category.id',
            'statusId' => 'status.id',
            'priorityId' => 'priority.id',
        ];
        foreach ($integerFilters as $key => $field) {
            $value = (int) ($filters[$key] ?? 0);
            if ($value > 0) {
                $queryBuilder->andWhere($field.' = :'.$key)->setParameter($key, $value, Types::INTEGER);
            }
        }
        if (\array_key_exists('assignedUserId', $filters) && '' !== (string) $filters['assignedUserId']) {
            $assignedUserId = (int) $filters['assignedUserId'];
            if (0 === $assignedUserId) {
                $queryBuilder->andWhere('assigned.id IS NULL');
            } elseif ($assignedUserId > 0) {
                $queryBuilder
                    ->andWhere('assigned.id = :assignedUserId')
                    ->setParameter('assignedUserId', $assignedUserId, Types::INTEGER)
                ;
            }
        }
        $course = trim((string) ($filters['course'] ?? ''));
        if ('' !== $course) {
            $queryBuilder
                ->andWhere('(course.title LIKE :course OR course.code LIKE :course OR course.visualCode LIKE :course)')
                ->setParameter('course', '%'.$course.'%', Types::STRING)
            ;
        }
        $startDate = $this->parseDate((string) ($filters['startDate'] ?? ''), false);
        if ($startDate instanceof DateTimeImmutable) {
            $queryBuilder
                ->andWhere('ticket.startDate >= :startDate')
                ->setParameter('startDate', $startDate, Types::DATETIME_IMMUTABLE)
            ;
        }
        $endDate = $this->parseDate((string) ($filters['endDate'] ?? ''), true);
        if ($endDate instanceof DateTimeImmutable) {
            $queryBuilder
                ->andWhere('ticket.startDate <= :endDate')
                ->setParameter('endDate', $endDate, Types::DATETIME_IMMUTABLE)
            ;
        }
    }

    private function parseDate(string $value, bool $endOfDay): ?DateTimeImmutable
    {
        if ('' === trim($value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date instanceof DateTimeImmutable) {
            throw new BadRequestHttpException('An invalid date filter was provided.');
        }

        return $endOfDay ? $date->setTime(23, 59, 59) : $date;
    }

    private function formatDate(mixed $value): string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s') : '';
    }

    private function safeSpreadsheetValue(string $value): string
    {
        return 1 === preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildUserLabel(array $row, string $prefix = ''): string
    {
        $key = static fn (string $name): string => '' === $prefix ? $name : $prefix.ucfirst($name);
        $fullName = trim((string) ($row[$key('firstname')] ?? '').' '.(string) ($row[$key('lastname')] ?? ''));

        return '' !== $fullName ? $fullName : (string) ($row[$key('username')] ?? '');
    }
}
