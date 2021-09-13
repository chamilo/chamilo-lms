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
        Session $session = null,
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
        $this->addTitleQueryBuilder($title, $qb);

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

    public function deleteAllByCourse(Course $course): void
    {
        $qb = $this->getResourcesByCourse($course);
        $resources = $qb->getQuery()->getResult();
        $em = $this->getEntityManager();

        /*foreach ($resources as $quiz) {
            $questions = $quiz->getQuestions();
            foreach ($questions as $question) {
                //$em->remove($question);
            }
            $em->remove($quiz);
        }*/
        //$em->flush();
    }

    private function addDateFilterQueryBuilder(DateTime $dateTime, QueryBuilder $qb = null): QueryBuilder
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

    private function addNotDeletedQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        $qb->andWhere('resource.active <> -1');

        return $qb;
    }

    private function addCategoryQueryBuilder(?int $categoryId = null, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        if (null !== $categoryId) {
            $qb
                ->andWhere('resource.exerciseCategory = :category_id')
                ->setParameter('category_id', $categoryId)
            ;
        }

        return $qb;
    }

    /**
     * @param int|null $active
     *                         null = no filter
     *                         -1 = deleted exercises
     *                         0 = inactive exercises
     *                         1 = active exercises
     *                         2 = all exercises (active and inactive)
     */
    private function addActiveQueryBuilder(?int $active = null, QueryBuilder $qb = null): QueryBuilder
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
}
