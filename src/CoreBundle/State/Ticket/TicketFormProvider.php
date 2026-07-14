<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Ticket;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Ticket\TicketForm;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\TicketPriority;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Entity\TicketStatus;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Service\Ticket\TicketWorkflowService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<TicketForm>
 */
final readonly class TicketFormProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private AccessUrlHelper $accessUrlHelper,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TicketForm
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

        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $projects = $this->getProjects($accessUrl);
        $project = $this->resolveProject($projects, $request->query->getInt('projectId'));
        $sessionId = max(0, $request->query->getInt('sessionId'));

        $result = new TicketForm();
        $result->isAdmin = $isAdmin;
        $result->canCreate = $isAdmin
            || 'true' === $this->settingsManager->getSetting('ticket.ticket_allow_student_add');
        $result->projects = array_map(
            static fn (TicketProject $item): array => [
                'id' => (int) $item->getId(),
                'title' => $item->getTitle(),
                'description' => $item->getDescription(),
            ],
            $projects,
        );
        $result->projectId = $project instanceof TicketProject ? (int) $project->getId() : 0;
        $result->sessionId = $sessionId;
        $result->categories = $project instanceof TicketProject ? $this->getCategories($project) : [];
        $result->statuses = $this->getStatuses($accessUrl);
        $result->priorities = $this->getPriorities($accessUrl);
        $result->sources = $this->getSources($isAdmin);
        $result->sessions = $this->getSessions(
            (int) $user->getId(),
            (int) $accessUrl->getId(),
            $isAdmin,
        );
        $result->courses = $this->getCourses(
            (int) $accessUrl->getId(),
            $sessionId,
            $isAdmin,
            $result->sessions,
        );
        $result->maxUploadSize = max(
            0,
            (int) $this->settingsManager->getSetting('message.message_max_upload_filesize'),
        );
        $result->csrfToken = $this->csrfTokenManager
            ->getToken(TicketWorkflowService::CSRF_TOKEN_ID)
            ->getValue()
        ;

        return $result;
    }

    /** @return array<int, TicketProject> */
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

    /** @param array<int, TicketProject> $projects */
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

    /** @return array<int, array{id: int, label: string, description: string|null, courseRequired: bool}> */
    private function getCategories(TicketProject $project): array
    {
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select([
                'category.id AS id',
                'category.title AS label',
                'category.description AS description',
                'category.courseRequired AS courseRequired',
            ])
            ->from(TicketCategory::class, 'category')
            ->andWhere('IDENTITY(category.project) = :projectId')
            ->setParameter('projectId', (int) $project->getId(), Types::INTEGER)
            ->orderBy('category.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $label = trim((string) $row['label']);
            if ($id <= 0 || '' === $label) {
                continue;
            }

            $result[] = [
                'id' => $id,
                'label' => $label,
                'description' => null !== $row['description'] ? (string) $row['description'] : null,
                'courseRequired' => (bool) ($row['courseRequired'] ?? false),
            ];
        }

        return $result;
    }

    /** @return array<int, array{id: int, label: string, code: string}> */
    private function getStatuses(AccessUrl $accessUrl): array
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

    /** @return array<int, array{id: int, label: string, code: string}> */
    private function getPriorities(AccessUrl $accessUrl): array
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

    /** @return array<int, array{id: string, label: string}> */
    private function getSources(bool $isAdmin): array
    {
        $sources = [
            ['id' => 'PLA', 'label' => (string) get_lang('Platform')],
        ];

        if (!$isAdmin) {
            return $sources;
        }

        $sources[] = ['id' => 'MAI', 'label' => (string) get_lang('E-mail')];
        $sources[] = ['id' => 'TEL', 'label' => (string) get_lang('Phone')];
        $sources[] = ['id' => 'PRE', 'label' => (string) get_lang('Presential')];

        return $sources;
    }

    /** @return array<int, array{id: int, label: string}> */
    private function getSessions(int $userId, int $accessUrlId, bool $isAdmin): array
    {
        if ($isAdmin) {
            $rows = $this->entityManager
                ->createQueryBuilder()
                ->select([
                    'DISTINCT sessionEntity.id AS id',
                    'sessionEntity.title AS label',
                ])
                ->from(Session::class, 'sessionEntity')
                ->innerJoin('sessionEntity.urls', 'urlRel')
                ->andWhere('IDENTITY(urlRel.url) = :accessUrlId')
                ->setParameter('accessUrlId', $accessUrlId, Types::INTEGER)
                ->orderBy('sessionEntity.title', 'ASC')
                ->getQuery()
                ->getArrayResult()
            ;

            return $this->normalizeSessionRows($rows);
        }

        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select([
                'DISTINCT sessionEntity.id AS id',
                'sessionEntity.title AS label',
            ])
            ->from(SessionRelUser::class, 'sessionRelUser')
            ->innerJoin('sessionRelUser.session', 'sessionEntity')
            ->innerJoin('sessionEntity.urls', 'urlRel')
            ->andWhere('IDENTITY(sessionRelUser.user) = :userId')
            ->andWhere('IDENTITY(urlRel.url) = :accessUrlId')
            ->andWhere('sessionRelUser.movedTo IS NULL')
            ->setParameter('userId', $userId, Types::INTEGER)
            ->setParameter('accessUrlId', $accessUrlId, Types::INTEGER)
            ->orderBy('sessionEntity.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $courseSessionRows = $this->entityManager
            ->createQueryBuilder()
            ->select([
                'DISTINCT sessionEntity.id AS id',
                'sessionEntity.title AS label',
            ])
            ->from(SessionRelCourseRelUser::class, 'sessionRelCourseRelUser')
            ->innerJoin('sessionRelCourseRelUser.session', 'sessionEntity')
            ->innerJoin('sessionEntity.urls', 'urlRel')
            ->andWhere('IDENTITY(sessionRelCourseRelUser.user) = :userId')
            ->andWhere('IDENTITY(urlRel.url) = :accessUrlId')
            ->setParameter('userId', $userId, Types::INTEGER)
            ->setParameter('accessUrlId', $accessUrlId, Types::INTEGER)
            ->orderBy('sessionEntity.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return $this->normalizeSessionRows([...$rows, ...$courseSessionRows]);
    }

    /**
     * @param array<int, array{id: int|string, label: string}> $rows
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function normalizeSessionRows(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $label = trim((string) $row['label']);
            if ($id <= 0 || '' === $label) {
                continue;
            }

            $result[$id] = [
                'id' => $id,
                'label' => $label,
            ];
        }

        uasort(
            $result,
            static fn (array $left, array $right): int => strcasecmp($left['label'], $right['label']),
        );

        return array_values($result);
    }

    /**
     * @param array<int, array{id: int, label: string}> $sessions
     *
     * @return array<int, array{id: int, label: string, code: string}>
     */
    private function getCourses(int $accessUrlId, int $sessionId, bool $isAdmin, array $sessions): array
    {
        if ($sessionId <= 0) {
            return [];
        }

        if (!$isAdmin && !in_array($sessionId, array_column($sessions, 'id'), true)) {
            throw new BadRequestHttpException('The requested session is not available to the authenticated user.');
        }

        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select([
                'DISTINCT course.id AS id',
                'course.title AS label',
                'course.code AS code',
            ])
            ->from(SessionRelCourse::class, 'sessionRelCourse')
            ->innerJoin('sessionRelCourse.course', 'course')
            ->innerJoin('course.urls', 'urlRel')
            ->andWhere('IDENTITY(sessionRelCourse.session) = :sessionId')
            ->andWhere('IDENTITY(urlRel.url) = :accessUrlId')
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->setParameter('accessUrlId', $accessUrlId, Types::INTEGER)
            ->orderBy('course.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $label = trim((string) $row['label']);
            if ($id <= 0 || '' === $label) {
                continue;
            }

            $result[] = [
                'id' => $id,
                'label' => $label,
                'code' => trim((string) $row['code']),
            ];
        }

        return $result;
    }
}
