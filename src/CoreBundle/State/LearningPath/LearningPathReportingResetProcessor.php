<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathReportingResetInput;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpIvInteraction;
use Chamilo\CourseBundle\Entity\CLpIvObjective;
use Chamilo\CourseBundle\Entity\CLpView;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/** @implements ProcessorInterface<LearningPathReportingResetInput, void> */
final readonly class LearningPathReportingResetProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SettingsManager $settingsManager,
        private LearningPathReportingProvider $reportingProvider,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): void {
        if (!$data instanceof LearningPathReportingResetInput) {
            throw new BadRequestHttpException('Invalid learning path reporting payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        $lp = $this->getLearningPath($uriVariables);
        $this->getEditableResourceLink($lp, $course, $session, $group, $this->security);
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);

        $userIds = $this->normalizeIds($data->userIds);
        if ([] === $userIds) {
            throw new BadRequestHttpException('At least one learner is required.');
        }

        $allowedUsers = $this->reportingProvider->getUsersForContext($lp, $course, $session, $request);
        if ([] !== array_diff($userIds, array_keys($allowedUsers))) {
            throw new AccessDeniedHttpException('A selected learner is outside the current learning path report.');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            if ($data->deleteExerciseAttempts) {
                $this->deleteExerciseAttempts($lp, $course, $session, $userIds);
            } else {
                $this->detachExerciseAttempts($lp, $course, $session, $userIds);
            }

            if ($this->minimumTimeAvailable((int) $course->getId(), $session?->getId())) {
                $this->deleteAccessCompletion($lp, $course, $session, $userIds);
            }

            $viewIds = $this->getViewIds($lp, $course, $session, $userIds);
            if ([] !== $viewIds) {
                $itemViewIds = $this->getItemViewIds($viewIds);
                if ([] !== $itemViewIds) {
                    $this->deleteInteractions($itemViewIds);
                    $this->deleteObjectives($itemViewIds);
                    $this->deleteItemViews($viewIds);
                }
                $this->deleteViews($viewIds);
            }

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    /** @param array<string, mixed> $uriVariables */
    private function getLearningPath(array $uriVariables): CLp
    {
        $lpId = (int) ($uriVariables['lpId'] ?? 0);
        if ($lpId <= 0) {
            throw new BadRequestHttpException('Invalid learning path id.');
        }

        $lp = $this->entityManager->getRepository(CLp::class)->find($lpId);
        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        return $lp;
    }

    /** @param array<int, int|string> $values @return array<int, int> */
    private function normalizeIds(array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        sort($ids);

        return array_values(array_unique($ids));
    }

    /** @param array<int, int> $userIds @return array<int, int> */
    private function getViewIds(CLp $lp, Course $course, ?Session $session, array $userIds): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('view.iid AS id')
            ->from(CLpView::class, 'view')
            ->andWhere('IDENTITY(view.lp) = :lpId')
            ->andWhere('IDENTITY(view.course) = :courseId')
            ->andWhere('IDENTITY(view.user) IN (:userIds)')
            ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
        ;
        $this->applySessionCondition($qb, 'view.session', $session);

        return array_map('intval', $qb->getQuery()->getSingleColumnResult());
    }

    /** @param array<int, int> $viewIds @return array<int, int> */
    private function getItemViewIds(array $viewIds): array
    {
        return array_map('intval', $this->entityManager->createQueryBuilder()
            ->select('itemView.iid AS id')
            ->from(CLpItemView::class, 'itemView')
            ->andWhere('IDENTITY(itemView.view) IN (:viewIds)')
            ->setParameter('viewIds', $viewIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getSingleColumnResult());
    }

    /** @param array<int, int> $itemViewIds */
    private function deleteInteractions(array $itemViewIds): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(CLpIvInteraction::class, 'interaction')
            ->andWhere('interaction.lpIvId IN (:itemViewIds)')
            ->setParameter('itemViewIds', $itemViewIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->execute()
        ;
    }

    /** @param array<int, int> $itemViewIds */
    private function deleteObjectives(array $itemViewIds): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(CLpIvObjective::class, 'objective')
            ->andWhere('objective.lpIvId IN (:itemViewIds)')
            ->setParameter('itemViewIds', $itemViewIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->execute()
        ;
    }

    /** @param array<int, int> $viewIds */
    private function deleteItemViews(array $viewIds): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(CLpItemView::class, 'itemView')
            ->andWhere('IDENTITY(itemView.view) IN (:viewIds)')
            ->setParameter('viewIds', $viewIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->execute()
        ;
    }

    /** @param array<int, int> $viewIds */
    private function deleteViews(array $viewIds): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(CLpView::class, 'view')
            ->andWhere('view.iid IN (:viewIds)')
            ->setParameter('viewIds', $viewIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->execute()
        ;
    }

    /** @param array<int, int> $userIds */
    private function detachExerciseAttempts(CLp $lp, Course $course, ?Session $session, array $userIds): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->update(TrackEExercise::class, 'exercise')
            ->set('exercise.origLpId', '0')
            ->set('exercise.origLpItemId', '0')
            ->set('exercise.origLpItemViewId', '0')
            ->andWhere('exercise.origLpId = :lpId')
            ->andWhere('IDENTITY(exercise.course) = :courseId')
            ->andWhere('IDENTITY(exercise.user) IN (:userIds)')
            ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
        ;
        $this->applySessionCondition($qb, 'exercise.session', $session);
        $qb->getQuery()->execute();
    }

    /** @param array<int, int> $userIds */
    private function deleteAccessCompletion(CLp $lp, Course $course, ?Session $session, array $userIds): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM track_e_access_complete
             WHERE tool = :tool
               AND tool_id = :toolId
               AND c_id = :courseId
               AND session_id = :sessionId
               AND user_id IN (:userIds)',
            [
                'tool' => 'learnpath',
                'toolId' => (int) $lp->getIid(),
                'courseId' => (int) $course->getId(),
                'sessionId' => (int) ($session?->getId() ?? 0),
                'userIds' => $userIds,
            ],
            [
                'tool' => Types::STRING,
                'toolId' => Types::INTEGER,
                'courseId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
                'userIds' => ArrayParameterType::INTEGER,
            ],
        );
    }

    /** @param array<int, int> $userIds */
    private function deleteExerciseAttempts(CLp $lp, Course $course, ?Session $session, array $userIds): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->delete(TrackEExercise::class, 'exercise')
            ->andWhere('exercise.origLpId = :lpId')
            ->andWhere('IDENTITY(exercise.course) = :courseId')
            ->andWhere('IDENTITY(exercise.user) IN (:userIds)')
            ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
        ;
        $this->applySessionCondition($qb, 'exercise.session', $session);
        $qb->getQuery()->execute();
    }

    private function minimumTimeAvailable(int $courseId, ?int $sessionId): bool
    {
        if (!$this->settingEnabled('lp.lp_minimum_time')) {
            return false;
        }

        $itemType = null !== $sessionId ? ExtraField::SESSION_FIELD_TYPE : ExtraField::COURSE_FIELD_TYPE;
        $value = $this->extraFieldValuesRepository->getValueByVariableAndItem(
            'new_tracking_system',
            $sessionId ?? $courseId,
            $itemType,
        );

        return $value instanceof ExtraFieldValues && 1 === (int) $value->getFieldValue();
    }

    private function settingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name);
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function applySessionCondition(QueryBuilder $qb, string $field, ?Session $session): void
    {
        if (null === $session) {
            $qb->andWhere($field.' IS NULL');

            return;
        }

        $qb->andWhere('IDENTITY('.$field.') = :sessionId')
            ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
        ;
    }
}
