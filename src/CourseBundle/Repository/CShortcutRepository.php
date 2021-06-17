<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CShortcut;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class CShortcutRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CShortcut::class);
    }

    public function getShortcutFromResource(AbstractResource $resource): ?CShortcut
    {
        $criteria = [
            'shortCutNode' => $resource->getResourceNode(),
        ];

        return $this->findOneBy($criteria);
    }

    public function addShortCut(AbstractResource $resource, ResourceInterface $parent, Course $course, Session $session = null): CShortcut
    {
        $shortcut = $this->getShortcutFromResource($resource);

        if (null === $shortcut) {
            $shortcut = new CShortcut();
            $shortcut
                ->setName($resource->getResourceName())
                ->setShortCutNode($resource->getResourceNode())
                ->setParent($parent)
                ->addCourseLink($course, $session)
            ;

            $this->create($shortcut);
        }

        return $shortcut;
    }

    public function removeShortCut(AbstractResource $resource): bool
    {
        $em = $this->getEntityManager();
        $shortcut = $this->getShortcutFromResource($resource);
        if (null !== $shortcut) {
            $em->remove($shortcut);
            $em->flush();

            return true;
        }

        return false;
    }

    /*public function getResources(ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('resource')
            ->select('resource')
            //->from($className, 'resource')
            ->innerJoin(
                'resource.resourceNode',
                'node'
            )
            ->leftJoin('node.resourceFile', 'file')
            //->innerJoin('node.resourceLinks', 'links')
            //->where('node.resourceType = :type')
            //->setParameter('type',$type)
        ;
        if (null !== $parentNode) {
            $qb->andWhere('node.parent = :parentNode');
            $qb->setParameter('parentNode', $parentNode);
        }

        return $qb;
    }*/
}
