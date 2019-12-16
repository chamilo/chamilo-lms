<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Component\Utils\ResourceSettings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceRepositoryInterface;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class CToolRepository extends ResourceRepository implements ResourceRepositoryInterface
{
    public function getResourceSettings(): ResourceSettings
    {
        $settings = new ResourceSettings();
        $settings
            ->setAllowNodeCreation(false)
            ->setAllowResourceCreation(false)
            ->setAllowResourceUpload(false)
        ;

        return $settings;
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null)
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();
        $checker = $this->getAuthorizationChecker();

        $reflectionClass = $repo->getClassMetadata()->getReflectionClass();

        // Check if this resource type requires to load the base course resources when using a session
        $loadBaseSessionContent = $reflectionClass->hasProperty('loadCourseResourcesInSession');

        $type = $this->getResourceType();

        $qb = $repo->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from($className, 'resource')
            ->innerJoin(
                ResourceNode::class,
                'node',
                Join::WITH,
                'resource.resourceNode = node.id'
            )
            ->innerJoin('node.resourceLinks', 'links')
            ->where('node.resourceType = :type')
            ->setParameter('type', $type);
        $qb
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

        ///var_dump($qb->getQuery()->getSQL(), $type->getId(), $parentNode->getId());exit;

        return $qb;
    }

    public function saveUpload(UploadedFile $file)
    {
        throw new AccessDeniedException();
    }

    public function saveResource(FormInterface $form, $course, $session, $fileType)
    {
    }

    public function getTitleColumn(Grid $grid): Column
    {
        return $grid->getColumn('name');
    }
}
