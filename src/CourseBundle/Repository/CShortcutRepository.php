<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Component\Utils\ResourceSettings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceRepositoryInterface;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CShortcutRepository.
 */
final class CShortcutRepository extends ResourceRepository implements ResourceRepositoryInterface
{
    public function getShortcutFromResource(AbstractResource $resource): ?CShortcut
    {
        $repo = $this->getRepository();
        $criteria = ['shortCutNode' => $resource->getResourceNode()];

        return $repo->findOneBy($criteria);
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null): QueryBuilder
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();

        $qb = $repo->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from($className, 'resource')
            ->innerJoin(
                ResourceNode::class,
                'node',
                Join::WITH,
                'resource.resourceNode = node.id'
            )
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

        //$qb->andWhere('node.creator = :creator');
        //$qb->setParameter('creator', $user);
        //var_dump($qb->getQuery()->getSQL(), $parentNode->getId());exit;

        return $qb;
    }

    public function getResourceSettings(): ResourceSettings
    {
        $settings = parent::getResourceSettings();

        $settings
            ->setAllowNodeCreation(false)
            ->setAllowResourceCreation(true)
            ->setAllowResourceUpload(false)
        ;

        return $settings;
    }

    public function saveUpload(UploadedFile $file)
    {
        return false;
    }

    public function saveResource(FormInterface $form, $course, $session, $fileType)
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

    public function getTitleColumn(Grid $grid): Column
    {
        return $grid->getColumn('name');
    }
}
