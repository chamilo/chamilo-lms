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

    public function findQuizzes(Course $course, Session $session = null, string $title = '', ?int $active = null)
    {
        $qb = $this->getResourcesByCourse($course, $session);

        $this->addDateFilterQueryBuilder(new DateTime(), $qb);
        $this->addActiveQueryBuilder($active, $qb);
        $this->addNotDeletedQueryBuilder($qb);
        $this->addTitleQueryBuilder($title, $qb);

        return $qb->getQuery()->getResult();
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
            ->andWhere("(
                (
                    resource.startTime <> '' AND
                    resource.startTime < :date AND
                    resource.endTime <> '' AND
                    resource.endTime > :date
                )  OR
                (resource.startTime <> '' AND resource.startTime < :date AND resource.endTime IS NULL) OR
                (resource.startTime IS NULL AND resource.endTime <> '' AND resource.endTime > :date) OR
                (resource.startTime IS NULL AND resource.endTime IS NULL)
                )
            ")
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

    private function addActiveQueryBuilder(?int $active = null, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        if (null !== $active) {
            $qb
                ->andWhere('resource.active = :active')
                ->setParameter('active', $active)
            ;
        }

        return $qb;
    }
}
