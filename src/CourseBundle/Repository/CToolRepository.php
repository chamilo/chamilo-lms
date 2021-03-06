<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class CToolRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CTool::class);
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        $checker = $this->getAuthorizationChecker();
        $reflectionClass = $this->getClassMetadata()->getReflectionClass();

        // Check if this resource type requires to load the base course resources when using a session
        $loadBaseSessionContent = $reflectionClass->hasProperty('loadCourseResourcesInSession');
        //$loadBaseSessionContent = true;
        //$classes = class_implements($this);

        $resourceTypeName = $this->getResourceTypeName();
        $qb = $this->createQueryBuilder('resource')
            ->select('resource')
            ->innerJoin(
                'resource.resourceNode',
                'node'
            )
            ->innerJoin('node.resourceLinks', 'links')
            ->innerJoin('node.resourceType', 'type')
            ->where('type.name = :type')
            ->setParameter('type', $resourceTypeName)
            ->andWhere('links.course = :course')
            ->setParameter('course', $course)
        ;

        $isAdmin = $checker->isGranted('ROLE_ADMIN') || $checker->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if (!$isAdmin) {
            $qb
                ->andWhere('links.visibility = :visibility')
                ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED)
            ;
        }

        if (null === $session) {
            $qb->andWhere('links.session IS NULL');
        } elseif ($loadBaseSessionContent) {
            // Load course base content.
            $qb->andWhere('links.session = :session OR links.session IS NULL');
            $qb->setParameter('session', $session);
        } else {
            // Load only session resources.
            $qb->andWhere('links.session = :session');
            $qb->setParameter('session', $session);
        }

        $qb->andWhere('node.parent = :parentNode');
        $qb->setParameter('parentNode', $parentNode);

        $qb->andWhere('links.group IS NULL');

        return $qb;
    }
}
