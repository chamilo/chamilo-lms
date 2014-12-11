<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * Class CNotebookRepository
 * @package Chamilo\NotebookBundle\Entity
 */
class CNotebookRepository extends EntityRepository
{
    /**
     * @param AbstractResource $resource
     * @param User $creator
     * @return ResourceNode
     */
    public function addResource(AbstractResource $resource, User $creator)
    {
        $resourceNode = new ResourceNode();
        $resourceNode
            ->setName($resource->getName())
            ->setCreator($creator)
            ->setTool($this->getToolName());

        $this->getEntityManager()->persist($resourceNode);
        $this->getEntityManager()->flush();

        return $resourceNode;
    }

    /**
     * @param AbstractResource $resource
     * @param User $user
     * @param Course $course
     * @return ResourceLink
     */
    public function addResourceToCourse(AbstractResource $resource, User $user, Course $course)
    {
        $resourceNode = $this->addResource($resource, $user);

        $resourceLink = new ResourceLink();
        $resourceLink->setResourceNode($resourceNode);
        $resourceLink->setCourse($course);

        $this->getEntityManager()->persist($resourceLink);
        $this->getEntityManager()->flush();

        return $resourceNode;
    }

    /**
     * @param AbstractResource $resource
     * @param User $user
     * @param Course $course
     * @return ResourceLink
     */
    public function getResourceByCourse(Course $course)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from('Chamilo\CoreBundle\Entity\Resource\ResourceNode', 'node')
            ->innerJoin('node.links', 'links')
            ->innerJoin($this->getClassName(), 'resource')
            ->where('node.tool = :tool')
            ->andWhere('links.course = :courseId')
            //->where('link.cId = ?', $course->getId())
            //->where('node.cId = 0')
            //->orderBy('node');
            ->setParameters(array(
                'tool'=> $this->getToolName(),
                'courseId' => $course->getId()
                )
            )
            ->getQuery()
        ;

        /*$query = $this->getEntityManager()->createQueryBuilder()
            ->select('notebook')
            ->from('ChamiloNotebookBundle:CNotebook', 'notebook')
            ->innerJoin('notebook.resourceNodes', 'node')
            //->innerJoin('node.links', 'link')
            ->where('node.tool = :tool')
            //->where('link.cId = ?', $course->getId())
            //->where('node.cId = 0')
            //->orderBy('node');
            ->setParameters(array(
                    'tool'=> 'notebook'
                )
            )
            ->getQuery()
        ;*/
        return $query->getResult();
    }

    /**
     * @param AbstractResource $resource
     * @param User $user
     * @param User $toUser
     * @return ResourceLink
     */
    public function addResourceToUser(AbstractResource $resource, User $user, User $toUser)
    {
        $resourceNode = $this->addResource($resource, $user);

        $resourceLink = new ResourceLink();
        $resourceLink->setResourceNode($resourceNode);

        $resourceLink->setUser($toUser);
        $this->getEntityManager()->persist($resourceLink);

        return $resourceLink;
    }

    /**
     * @param AbstractResource $resource
     * @param User $user
     * @param Course $course
     * @param Session $session
     */
    public function addResourceToSession(AbstractResource $resource, User $user, Course $course, Session $session)
    {
        $resourceLink = $this->addResourceToCourse($resource, $user, $course);
        $resourceLink->setSession($session);
        $this->getEntityManager()->persist($resourceLink);
    }

    /**
     * @param AbstractResource $resource
     * @param User $user
     * @param Course $course
     * @param CGroupInfo $group
     */
    public function addResourceToGroup(AbstractResource $resource, User $user, Course $course, CGroupInfo $group)
    {
        $resourceLink = $this->addResourceToCourse($resource, $user, $course);
        $resourceLink->setGroup($group);
        $this->getEntityManager()->persist($resourceLink);
    }

    /**
     * @return string
     */
    public function getToolName()
    {
        return 'notebook';
    }
}
