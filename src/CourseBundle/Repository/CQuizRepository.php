<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceWithLinkInterface;
use Chamilo\CourseBundle\Entity\CQuiz;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;

final class CQuizRepository extends ResourceRepository implements ResourceWithLinkInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CQuiz::class);
    }

    public function findAllByCourse(
        Course $course,
        ?Session $session = null,
        ?string $title = null,
        ?int $active = null,
        bool $onlyPublished = true,
        ?int $categoryId = null,
        bool $includeDeleted = false
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse(
            $course,
            $session,
            null,
            null,
            $onlyPublished
        );

        if ($onlyPublished) {
            $this->addDateFilterQueryBuilder(new DateTime(), $qb);
        }

        $this->addCategoryQueryBuilder($categoryId, $qb);
        $this->addActiveQueryBuilder($active, $qb);

        if (false === $includeDeleted) {
            $this->addNotDeletedQueryBuilder($qb);
        }

        if (!empty($title)) {
            $this->addTitleQueryBuilder($title, $qb);
        }

        return $qb;
    }

    public function getLink(ResourceInterface $resource, RouterInterface $router, array $extraParams = []): string
    {
        $exerciseId = (int) $resource->getResourceIdentifier();
        $courseNodeId = $resource instanceof CQuiz
            ? (int) ($resource->getResourceNode()?->getParent()?->getId() ?? 0)
            : 0;

        if ($exerciseId <= 0 || $courseNodeId <= 0) {
            $params = array_merge(['exerciseId' => $exerciseId], $extraParams);

            return '/main/exercise/overview.php?'.http_build_query($params);
        }

        unset($extraParams['exerciseId'], $extraParams['node'], $extraParams['legacy']);
        $url = '/resources/exercise/'.$courseNodeId.'/'.$exerciseId.'/overview';

        return [] === $extraParams ? $url : $url.'?'.http_build_query($extraParams);
    }

    public function findAutoLaunchableQuizByCourseAndSession(Course $course, ?Session $session = null): ?int
    {
        $qb = $this->getResourcesByCourse($course, $session)
            ->select('resource.iid')
            ->andWhere('resource.autoLaunch = 1')
        ;

        $qb->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result ? $result['iid'] : null;
    }

    /**
     * The exercise, if it is linked to this course — and, when there is one, to this session or
     * to the base course. Deleted and expired links do not count.
     *
     * Says nothing about visibility: an unpublished exercise still belongs to its course, and
     * the caller decides what to do about that. Returning null for both "no such exercise" and
     * "not in this context" is deliberate, so a caller cannot disclose the difference.
     *
     * The course and session terms come from ResourceRepository, which also treats a link with
     * session 0 as base course content — something the legacy tables do write. The group term of
     * addCourseSessionGroupQueryBuilder() is left out on purpose: it would reject an exercise
     * linked to a group, which still belongs to the course.
     */
    public function findInCourseContext(int $exerciseId, Course $course, ?Session $session): ?CQuiz
    {
        $queryBuilder = $this->createQueryBuilder('quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', $exerciseId)
            ->setMaxResults(1)
        ;

        $this->addCourseQueryBuilder($course, $queryBuilder);

        if (null === $session) {
            $this->addSessionNullQueryBuilder($queryBuilder);
        } else {
            $this->addSessionAndBaseContentQueryBuilder($session, $queryBuilder);
        }

        $quiz = $queryBuilder->getQuery()->getOneOrNullResult();

        return $quiz instanceof CQuiz ? $quiz : null;
    }

    /**
     * The same lookup as findInCourseContext(), plus the visibility of the link it matched.
     *
     * Every exercise endpoint runs this query and then applies its own rule on the visibility —
     * some ignore it, some demand a teacher, some accept an unpublished exercise reached from a
     * learning path. Only the query is shared; the rule stays with the caller.
     *
     * @param bool $sessionOnly exclude base course content when a session is given, as the
     *                          export and notification services do
     *
     * @return array{quiz: CQuiz, visibility: int}|null
     */
    public function findInCourseContextWithVisibility(
        int $exerciseId,
        Course $course,
        ?Session $session,
        bool $sessionOnly = false
    ): ?array {
        $queryBuilder = $this->createQueryBuilder('quiz')
            ->addSelect('links.visibility AS linkVisibility')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', $exerciseId)
            ->setMaxResults(1)
        ;

        $this->addCourseQueryBuilder($course, $queryBuilder);

        if (null === $session) {
            $this->addSessionNullQueryBuilder($queryBuilder);
        } elseif ($sessionOnly) {
            $this->addSessionOnlyQueryBuilder($session, $queryBuilder);
        } else {
            $this->addSessionAndBaseContentQueryBuilder($session, $queryBuilder);
        }

        $row = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!\is_array($row) || !($row[0] ?? null) instanceof CQuiz) {
            return null;
        }

        return [
            'quiz' => $row[0],
            'visibility' => (int) ($row['linkVisibility'] ?? 0),
        ];
    }

    public function findQuizzesUsingQuestion(int $questionId, int $excludeQuizId = 0): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('quiz', 'rn', 'rl', 'course', 'session')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.questions', 'rel')
            ->innerJoin('quiz.resourceNode', 'rn')
            ->leftJoin('rn.resourceLinks', 'rl')
            ->leftJoin('rl.course', 'course')
            ->leftJoin('rl.session', 'session')
            ->where('rel.question = :questionId')
            ->setParameter('questionId', $questionId)
            ->groupBy('quiz.iid')
        ;

        if ($excludeQuizId > 0) {
            $qb
                ->andWhere('quiz.iid != :excludeQuizId')
                ->setParameter('excludeQuizId', $excludeQuizId)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    private function addDateFilterQueryBuilder(DateTime $dateTime, ?QueryBuilder $qb = null): void
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        $qb
            ->andWhere('(
                (
                    resource.startTime IS NOT NULL AND
                    resource.startTime < :date AND
                    resource.endTime IS NOT NULL AND
                    resource.endTime > :date
                ) OR
                (
                    resource.startTime IS NOT NULL AND
                    resource.startTime < :date AND
                    resource.endTime IS NULL
                ) OR
                (
                    resource.startTime IS NULL AND
                    resource.endTime IS NOT NULL AND
                    resource.endTime > :date
                ) OR
                (
                    resource.startTime IS NULL AND
                    resource.endTime IS NULL
                )
            )')
            ->setParameter('date', $dateTime)
        ;
    }

    private function addNotDeletedQueryBuilder(?QueryBuilder $qb = null): void
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        $qb
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
        ;
    }

    private function addCategoryQueryBuilder(?int $categoryId = null, ?QueryBuilder $qb = null): void
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        if (null !== $categoryId) {
            $qb
                ->andWhere('resource.quizCategory = :category_id')
                ->setParameter('category_id', $categoryId)
            ;
        }
    }

    /**
     * If $active is provided (any value), enforce links.visibility = 2 (visible).
     * If $active is null, do not add a visibility filter here.
     */
    private function addActiveQueryBuilder(?int $active = null, ?QueryBuilder $qb = null): void
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        if (null !== $active) {
            $qb
                ->andWhere('links.visibility = :visibility')
                ->setParameter('visibility', 2)
            ;
        }
    }
}
