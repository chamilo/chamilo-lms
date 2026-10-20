<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\Persistence\ManagerRegistry;

final class CQuizQuestionRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CQuizQuestion::class);
    }

    public function getHotSpotImageUrl(CQuizQuestion $resource): string
    {
        $params = [
            'mode' => 'view',
            'filter' => 'hotspot_question',
        ];

        return $this->getResourceFileUrl($resource, $params);
    }

    /**
     * Finds a question by iid only when it has a live resource link in the given course (and, with
     * a session, in that session or the base course), as CQuizRepository::findInCourseContext()
     * does for exercises. Used to keep a question id from another course out of course-scoped
     * actions such as the legacy QTI2 question export.
     */
    public function findInCourseContext(int $questionId, Course $course, ?Session $session): ?CQuizQuestion
    {
        $queryBuilder = $this->createQueryBuilder('question')
            ->innerJoin('question.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('question.iid = :questionId')
            ->andWhere('links.deletedAt IS NULL')
            ->setParameter('questionId', $questionId)
            ->setMaxResults(1)
        ;

        $this->addCourseQueryBuilder($course, $queryBuilder);

        if (null === $session) {
            $this->addSessionNullQueryBuilder($queryBuilder);
        } else {
            $this->addSessionAndBaseContentQueryBuilder($session, $queryBuilder);
        }

        $question = $queryBuilder->getQuery()->getOneOrNullResult();

        return $question instanceof CQuizQuestion ? $question : null;
    }
}
