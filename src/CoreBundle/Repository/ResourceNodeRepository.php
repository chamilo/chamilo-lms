<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;

/**
 * Class ResourceNodeRepository.
 */
class ResourceNodeRepository extends MaterializedPathRepository
{
    /**
     * @todo filter files, check status
     */
    public function getSize(ResourceNode $resourceNode, ResourceType $type, Course $course = null, Session $session = null): int
    {
        $qb = $this->createQueryBuilder('node')
            ->select('SUM(file.size) as total')
            ->innerJoin('node.resourceFile', 'file')
            ->innerJoin('node.resourceLinks', 'l')
            ->where('node.resourceType = :type')
            ->setParameter('type', $type)
            ->andWhere('node.parent = :parentNode')
            ->setParameter('parentNode', $resourceNode)
            ->andWhere('file IS NOT NULL')
            ->andWhere('l.visibility <> :visibility')
            ->setParameter('visibility', ResourceLink::VISIBILITY_DELETED)
        ;

        if ($course) {
            $qb
                ->andWhere('l.course = :course')
                ->setParameter('course', $course);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
