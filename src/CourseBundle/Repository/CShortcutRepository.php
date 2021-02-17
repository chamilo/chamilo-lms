<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CShortcut;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;

/**
 * Class CShortcutRepository.
 */
final class CShortcutRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CShortcut::class);
    }

    public function getShortcutFromResource(AbstractResource $resource): ?CShortcut
    {
        $criteria = ['shortCutNode' => $resource->getResourceNode()];

        return $this->findOneBy($criteria);
    }

    public function addShortCut(AbstractResource $resource, $parent, Course $course, Session $session = null)
    {
        $shortcut = $this->getShortcutFromResource($resource);

        if (null === $shortcut) {
            $shortcut = new CShortcut();
            $shortcut
                ->setName($resource->getResourceName())
                ->setShortCutNode($resource->getResourceNode())
                ->setParent($parent)
                ->addCourseLink($course, $session);

            $this->create($shortcut);
        }
    }

    public function removeShortCut(AbstractResource $resource)
    {
        $em = $this->getEntityManager();
        $shortcut = $this->getShortcutFromResource($resource);
        if (null !== $shortcut) {
            $em->remove($shortcut);
            $em->flush();
        }
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
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
        /*$qb
            ->andWhere('links.visibility = :visibility')
            ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED)
        ;*/

        if (null !== $parentNode) {
            $qb->andWhere('node.parent = :parentNode');
            $qb->setParameter('parentNode', $parentNode);
        }

        return $qb;
    }

    public function setResourceProperties(FormInterface $form, $course, $session, $fileType)
    {
        $newResource = $form->getData();
        $newResource
            ->setCourse($course)
            ->setSession($session)
            ->setFiletype($fileType)
            //->setTitle($title) // already added in $form->getData()
            ->setReadonly(false)
        ;

        return $newResource;
    }
}
