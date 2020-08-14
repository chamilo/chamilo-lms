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
use Symfony\Component\Form\FormInterface;

/**
 * Class CShortcutRepository.
 */
final class CShortcutRepository extends ResourceRepository
{
    public function getShortcutFromResource(AbstractResource $resource): ?CShortcut
    {
        $repo = $this->getRepository();
        $criteria = ['shortCutNode' => $resource->getResourceNode()];

        return $repo->findOneBy($criteria);
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();

        $qb = $repo->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from($className, 'resource')
            ->innerJoin(
                'resource.resourceNode',
                'node'
            )
            ->innerJoin('node.resourceFile', 'file')
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
