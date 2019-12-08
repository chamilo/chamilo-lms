<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceType;
use Doctrine\ORM\Query\Expr\Join;
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;

/**
 * Class ResourceNodeRepository.
 */
class ResourceNodeRepository extends MaterializedPathRepository
{
    /**
     * @todo filter files, check status
     */
    public function getSize(ResourceNode $resourceNode, ResourceType $type): int
    {
        $qb = $this->createQueryBuilder('node')
            ->select('SUM(file.size) as total')
            ->innerJoin('node.resourceFile', 'file')
            //->innerJoin('node.resourceLinks', 'links')
            ->where('node.resourceType = :type')
            ->setParameter('type', $type)
            ->andWhere('node.parent = :parentNode')
            ->setParameter('parentNode', $resourceNode)
            ->andWhere('file IS NOT NULL')
        ;

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result;
    }
}
