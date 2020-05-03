<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;

final class CToolRepository extends ResourceRepository
{
    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null): QueryBuilder
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();
        $checker = $this->getAuthorizationChecker();
        $reflectionClass = $repo->getClassMetadata()->getReflectionClass();

        // Check if this resource type requires to load the base course resources when using a session
        $loadBaseSessionContent = $reflectionClass->hasProperty('loadCourseResourcesInSession');

        $resourceTypeName = $this->getResourceTypeName();
        $qb = $repo->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from($className, 'resource')
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

        if (false === $isAdmin) {
            $qb
                ->andWhere('links.visibility = :visibility')
                ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED)
            ;
        }

        if (null === $session) {
            $qb->andWhere('links.session IS NULL');
        } else {
            if ($loadBaseSessionContent) {
                // Load course base content.
                $qb->andWhere('links.session = :session OR links.session IS NULL');
                $qb->setParameter('session', $session);
            } else {
                // Load only session resources.
                $qb->andWhere('links.session = :session');
                $qb->setParameter('session', $session);
            }
        }

        $qb->andWhere('node.parent = :parentNode');
        $qb->setParameter('parentNode', $parentNode);

        $qb->andWhere('links.group IS NULL');

        return $qb;
    }
}
