<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\HotPotatoes;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\HotPotatoesAttempt;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class HotPotatoesAttemptService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function start(int $documentId, string $entrypoint, int $userId, Course $course, ?Session $session, ?CGroup $group): HotPotatoesAttempt
    {
        // Reloads and retries must not create an unlimited series of open attempts.
        $latest = $this->entityManager->getRepository(HotPotatoesAttempt::class)->findOneBy(
            [
                'documentId' => $documentId,
                'entrypoint' => $entrypoint,
                'userId' => $userId,
                'courseId' => (int) $course->getId(),
                'sessionId' => (int) ($session?->getId() ?? 0),
                'groupId' => (int) ($group?->getIid() ?? 0),
            ],
            ['id' => 'DESC'],
        );
        if ($latest instanceof HotPotatoesAttempt && null === $latest->getCompletedAt()
            && $latest->getStartedAt() > new DateTimeImmutable('-10 seconds', new DateTimeZone('UTC'))
        ) {
            return $latest;
        }

        $attempt = new HotPotatoesAttempt(
            $documentId,
            $entrypoint,
            $userId,
            (int) $course->getId(),
            (int) ($session?->getId() ?? 0),
            (int) ($group?->getIid() ?? 0),
        );
        $this->entityManager->persist($attempt);
        $this->entityManager->flush();

        return $attempt;
    }

    /**
     * Completion is a learner acknowledgement, not a verified score.
     */
    public function complete(int $attemptId, int $documentId, int $userId, Course $course, ?Session $session, ?CGroup $group): HotPotatoesAttempt
    {
        $attempt = $this->entityManager->getRepository(HotPotatoesAttempt::class)->find($attemptId);
        if (!$attempt instanceof HotPotatoesAttempt || $attempt->getDocumentId() !== $documentId) {
            throw new NotFoundHttpException('Hot Potatoes attempt not found.');
        }
        if (!$this->matchesContext($attempt, $course, $session, $group) || $attempt->getUserId() !== $userId) {
            throw new AccessDeniedHttpException('The Hot Potatoes attempt belongs to another user or context.');
        }

        $attempt->complete();
        $this->entityManager->flush();

        return $attempt;
    }

    /**
     * @return list<HotPotatoesAttempt>
     */
    public function list(int $documentId, int $userId, Course $course, ?Session $session, ?CGroup $group, bool $canManage): array
    {
        $query = $this->entityManager->getRepository(HotPotatoesAttempt::class)->createQueryBuilder('attempt')
            ->andWhere('attempt.documentId = :documentId')
            ->andWhere('attempt.courseId = :courseId')
            ->andWhere('attempt.sessionId = :sessionId')
            ->andWhere('attempt.groupId = :groupId')
            ->setParameter('documentId', $documentId)
            ->setParameter('courseId', (int) $course->getId())
            ->setParameter('sessionId', (int) ($session?->getId() ?? 0))
            ->setParameter('groupId', (int) ($group?->getIid() ?? 0))
            ->orderBy('attempt.startedAt', 'DESC')
            ->addOrderBy('attempt.id', 'DESC')
            ->setMaxResults(100)
        ;
        if (!$canManage) {
            $query->andWhere('attempt.userId = :userId')->setParameter('userId', $userId);
        }

        return $query->getQuery()->getResult();
    }

    public function matchesContext(HotPotatoesAttempt $attempt, Course $course, ?Session $session, ?CGroup $group): bool
    {
        return $attempt->getCourseId() === (int) $course->getId()
            && $attempt->getSessionId() === (int) ($session?->getId() ?? 0)
            && $attempt->getGroupId() === (int) ($group?->getIid() ?? 0);
    }
}
