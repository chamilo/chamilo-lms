<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Traits\Repository\ORM\NestedTreeRepositoryTrait;
use Chamilo\CourseBundle\Entity\CSurvey;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class CSurveyRepository extends ResourceRepository
{
    use NestedTreeRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CSurvey::class);

        $this->initializeTreeRepository($this->getEntityManager(), $this->getClassMetadata());
    }

    public function findAllByCourse(
        Course $course,
        Session $session = null,
        ?string $title = null,
        ?string $language = null,
        ?User $author = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session);

        $this->addTitleQueryBuilder($title, $qb);
        $this->addLanguageQueryBuilder($language, $qb);
        $this->addCreatorQueryBuilder($author, $qb);

        return $qb;
    }

    public function getTreeByCourse(Course $course, Session $session = null): void
    {
        /*$qb = $this->getResourcesByCourse($course, $session);

        $this->getEntityManager()->getConfiguration()->addCustomHydrationMode(
            'tree',
            TreeObjectHydrator::class
        );

        /*$options = ['decorate' => true,   'html' => false];
        $tree = $this->buildTree($qb->getQuery()->getArrayResult(), $options);*/

        //return $qb->getQuery()->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)->getResult('tree');
    }

    protected function addTitleQueryBuilder(?string $title, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        if (null === $title) {
            return $qb;
        }

        $qb
            ->andWhere('resource.code = :title')
            ->andWhere('node.title = :title')
            ->setParameter('title', $title)
        ;

        return $qb;
    }

    private function addLanguageQueryBuilder(?string $language = null, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        if (null !== $language) {
            $qb
                ->andWhere('resource.lang = :lang')
                ->setParameter('lang', $language)
            ;
        }

        return $qb;
    }
}
