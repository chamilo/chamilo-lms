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
        ?int $categoryId = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session);

        if ($onlyPublished) {
            $this->addDateFilterQueryBuilder(new DateTime(), $qb);
        }
        $this->addCategoryQueryBuilder($categoryId, $qb);
        $this->addActiveQueryBuilder($active, $qb);
        $this->addNotDeletedQueryBuilder($qb);
        if (!empty($title)) {
            $this->addTitleQueryBuilder($title, $qb);
        }

        return $qb;
    }

    public function getLink(ResourceInterface $resource, RouterInterface $router, array $extraParams = []): string
    {
        $params = [
            'name' => 'exercise/overview.php',
            'exerciseId' => $resource->getResourceIdentifier(),
        ];
        if (!empty($extraParams)) {
            $params = array_merge($params, $extraParams);
        }

        return $router->generate('legacy_main', $params);
    }

    private function addDateFilterQueryBuilder(DateTime $dateTime, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        $qb
            ->andWhere('(
                (
                    resource.startTime IS NOT NULL AND
                    resource.startTime < :date AND
                    resource.endTime IS NOT NULL AND
                    resource.endTime > :date
                )  OR
                (resource.startTime IS NOT NULL AND resource.startTime < :date AND resource.endTime IS NULL) OR
                (resource.startTime IS NULL AND resource.endTime IS NOT NULL AND resource.endTime > :date) OR
                (resource.startTime IS NULL AND resource.endTime IS NULL)
                )
            ')
            ->setParameter('date', $dateTime)
        ;

        return $qb;
    }

    private function addNotDeletedQueryBuilder(?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        $qb->andWhere('resource.active <> -1');

        return $qb;
    }

    private function addCategoryQueryBuilder(?int $categoryId = null, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        if (null !== $categoryId) {
            $qb
                ->andWhere('resource.quizCategory = :category_id')
                ->setParameter('category_id', $categoryId)
            ;
        }

        return $qb;
    }

    /**
     * Adds resource.active filter.
     *
     * The active parameter can be one of the following values.
     *
     * - null = no filter
     * - -1 = deleted exercises
     * - 0 = inactive exercises
     * - 1 = active exercises
     * - 2 = all exercises (active and inactive)
     */
    private function addActiveQueryBuilder(?int $active = null, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        if (null !== $active) {
            if (2 === $active) {
                $qb
                    ->andWhere('resource.active = 1 OR resource.active = 0')
                ;
            } else {
                $qb
                    ->andWhere('resource.active = :active')
                    ->setParameter('active', $active)
                ;
            }
        }

        return $qb;
    }

    /**
     * Finds the auto-launchable quiz for the given course and session.
     */
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
     * Finds quizzes that are using a given question, optionally excluding one quiz.
     */
    public function findQuizzesUsingQuestion(int $questionId, int $excludeQuizId = 0): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('quiz', 'rn', 'rl', 'course', 'session')
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
            $qb->andWhere('quiz.iid != :excludeQuizId')
                ->setParameter('excludeQuizId', $excludeQuizId)
            ;
        }

        return $qb->getQuery()->getResult();
    }
}
