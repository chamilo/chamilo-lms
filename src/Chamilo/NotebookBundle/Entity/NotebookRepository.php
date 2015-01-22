<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceRights;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\ToolResourceRights;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\Group;
use Chamilo\UserBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Doctrine\Common\Collections\ArrayCollection;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * Class NotebookRepository
 * @package Chamilo\NotebookBundle\Entity
 */
class NotebookRepository extends EntityRepository
{
    /**
     * Creates a ResourceNode
     * @param AbstractResource $resource
     * @param User $creator
     * @return ResourceNode
     */
    public function addResourceNode(AbstractResource $resource, User $creator)
    {
        $resourceNode = new ResourceNode();
        $resourceNode
            ->setName($resource->getName())
            ->setCreator($creator)
            ->setTool($this->getTool())
        ;

        $this->getEntityManager()->persist($resourceNode);
        $this->getEntityManager()->flush();

        return $resourceNode;
    }

    /**
     * @param ResourceNode $resourceNode
     * @return ResourceLink
     */
    public function addResourceOnlyToMe(
        ResourceNode $resourceNode
    ) {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->setPrivate(true)
        ;

        $this->getEntityManager()->persist($resourceLink);
        $this->getEntityManager()->flush();

        return $resourceLink;
    }

    /**
     * @param ResourceNode $resourceNode
     * @param ResourceRights $right
     * @return ResourceLink
     */
    public function addResourceToEveryone(
        ResourceNode $resourceNode,
        ResourceRights $right
    ) {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->addRight($right)
            ->setPublic(true)
        ;

        $this->getEntityManager()->persist($resourceLink);
        $this->getEntityManager()->flush();

       return $resourceLink;
    }

    /**
     * @param ResourceNode $resourceNode
     * @param Course $course
     * @param ResourceRights $right
     * @return ResourceLink
     */
    public function addResourceToCourse(
        ResourceNode $resourceNode,
        Course $course,
        ResourceRights $right
    ) {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->setCourse($course)
            ->addRight($right)
        ;
        $this->getEntityManager()->persist($resourceLink);
        $this->getEntityManager()->flush();

        return $resourceLink;
    }

    /**
     * @param ResourceNode $resourceNode
     * @param User $toUser
     * @return ResourceLink
     */
    public function addResourceToUser(ResourceNode $resourceNode, User $toUser)
    {
        $resourceLink = $this->addResourceNodeToUser($resourceNode, $toUser);
        $this->getEntityManager()->persist($resourceLink);

        return $resourceLink;
    }

    /**
     * @param ResourceNode $resourceNode
     * @param array $userList User id list
     */
    public function addResourceToUserList(ResourceNode $resourceNode, $userList)
    {
        if (!empty($userList)) {
            foreach ($userList as $userId) {
                $toUser = $this->getEntityManager()->getRepository(
                    'ChamiloUserBundle:User'
                )->find($userId);

                $resourceLink = $this->addResourceNodeToUser(
                    $resourceNode,
                    $toUser
                );
                $this->getEntityManager()->persist($resourceLink);
            }
        }
    }

    /**
     * @param ResourceNode $resourceNode
     * @param User $toUser
     * @return ResourceLink
     */
    public function addResourceNodeToUser(ResourceNode $resourceNode, User $toUser)
    {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->setUser($toUser)
        ;

        return $resourceLink;
    }

    /**
     * @param ResourceNode $resourceNode
     * @param Course $course
     * @param Session $session
     * @param ResourceRights $right
     * @return ResourceLink
     */
    public function addResourceToSession(
        ResourceNode $resourceNode,
        Course $course,
        Session $session,
        ResourceRights $right
    ) {
        $resourceLink = $this->addResourceToCourse($resourceNode, $course, $right);
        $resourceLink->setSession($session);
        $this->getEntityManager()->persist($resourceLink);

        return $resourceLink;
    }

    /**
     * @param ResourceNode $resourceNode
     * @param Course $course
     * @param CGroupInfo $group
     * @param ResourceRights $right
     * @return ResourceLink
     */
    public function addResourceToCourseGroup(
        ResourceNode $resourceNode,
        Course $course,
        CGroupInfo $group,
        ResourceRights $right
    ) {
        $resourceLink = $this->addResourceToCourse($resourceNode, $course, $right);
        $resourceLink->setGroup($group);
        $this->getEntityManager()->persist($resourceLink);

        return $resourceLink;
    }

    /**
     * @param ResourceNode $resourceNode
     * @param Usergroup $group
     * @param ResourceRights $right
     * @return ResourceLink
     */
    public function addResourceToGroup(
        ResourceNode $resourceNode,
        Usergroup $group,
        ResourceRights $right
    ) {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->setUserGroup($group)
            ->addRight($right)
        ;

        return $resourceLink;
    }

    /**
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
            ->andWhere('links.course = :course')
            //->where('link.cId = ?', $course->getId())
            //->where('node.cId = 0')
            //->orderBy('node');
            ->setParameters(array(
                'tool'=> $this->getTool(),
                'course' => $course
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
     * @return Tool
     */
    public function getTool()
    {
        return $this->getEntityManager()
            ->getRepository('ChamiloCoreBundle:Tool')
            ->findOneByName($this->getToolName());
    }

    /**
     * @return string
     */
    public function getToolName()
    {
        return 'notebook';
    }
}
