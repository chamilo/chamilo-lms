<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceRight;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class ResourceRepository.
 *
 * @package Chamilo\CoreBundle\Entity
 */
class ResourceRepository
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Creates a ResourceNode.
     *
     * @param AbstractResource $resource
     * @param User             $creator
     * @param AbstractResource $parent
     *
     * @return ResourceNode
     */
    public function addResourceNode(
        AbstractResource $resource,
        User $creator,
        AbstractResource $parent = null
    ): ResourceNode {
        $resourceNode = new ResourceNode();

        $tool = $this->getTool($resource->getToolName());
        $resourceType = $this->getEntityManager()->getRepository('ChamiloCoreBundle:Resource\ResourceType')->findOneBy(
            ['name' => $resource->getToolName()]
        );
        $resourceNode
            ->setName($resource->getResourceName())
            ->setCreator($creator)
            ->setResourceType($resourceType);

        if ($parent !== null) {
            $resourceNode->setParent($parent->getResourceNode());
        }

        $this->getEntityManager()->persist($resourceNode);
        $this->getEntityManager()->flush();

        return $resourceNode;
    }

    public function addResourceMedia(ResourceNode $resourceNode, $file)
    {
    }

    /**
     * @param ResourceNode $resourceNode
     *
     * @return ResourceLink
     */
    public function addResourceOnlyToMe(ResourceNode $resourceNode): ResourceLink
    {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->setPrivate(true);

        $this->getEntityManager()->persist($resourceLink);
        $this->getEntityManager()->flush();

        return $resourceLink;
    }

    /**
     * @param ResourceNode  $resourceNode
     * @param ResourceRight $right
     *
     * @return ResourceLink
     */
    public function addResourceToEveryone(ResourceNode $resourceNode, ResourceRight $right): ResourceLink
    {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->addResourceRight($right)
        ;

        $this->getEntityManager()->persist($resourceLink);
        $this->getEntityManager()->flush();

        return $resourceLink;
    }

    /**
     * @param ResourceNode  $resourceNode
     * @param Course        $course
     * @param ResourceRight $right
     *
     * @return ResourceLink
     */
    public function addResourceToCourse(ResourceNode $resourceNode, Course $course, ResourceRight $right): ResourceLink
    {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->setCourse($course)
            ->addResourceRight($right);
        $this->getEntityManager()->persist($resourceLink);
        $this->getEntityManager()->flush();

        return $resourceLink;
    }

    /**
     * @param ResourceNode $resourceNode
     * @param User         $toUser
     *
     * @return ResourceLink
     */
    public function addResourceToUser(ResourceNode $resourceNode, User $toUser): ResourceLink
    {
        $resourceLink = $this->addResourceNodeToUser($resourceNode, $toUser);
        $this->getEntityManager()->persist($resourceLink);

        return $resourceLink;
    }

    /**
     * @param ResourceNode $resourceNode
     * @param array        $userList     User id list
     */
    public function addResourceToUserList(ResourceNode $resourceNode, array $userList)
    {
        $em = $this->getEntityManager();

        if (!empty($userList)) {
            foreach ($userList as $userId) {
                $toUser = $em->getRepository('ChamiloUserBundle:User')->find($userId);

                $resourceLink = $this->addResourceNodeToUser($resourceNode, $toUser);
                $em->persist($resourceLink);
            }
        }
    }

    /**
     * @param ResourceNode $resourceNode
     * @param User         $toUser
     *
     * @return ResourceLink
     */
    public function addResourceNodeToUser(ResourceNode $resourceNode, User $toUser): ResourceLink
    {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->setUser($toUser);

        return $resourceLink;
    }

    /**
     * @param ResourceNode  $resourceNode
     * @param Course        $course
     * @param Session       $session
     * @param ResourceRight $right
     *
     * @return ResourceLink
     */
    public function addResourceToSession(
        ResourceNode $resourceNode,
        Course $course,
        Session $session,
        ResourceRight $right
    ) {
        $resourceLink = $this->addResourceToCourse(
            $resourceNode,
            $course,
            $right
        );
        $resourceLink->setSession($session);
        $this->getEntityManager()->persist($resourceLink);

        return $resourceLink;
    }

    /**
     * @param ResourceNode  $resourceNode
     * @param Course        $course
     * @param CGroupInfo    $group
     * @param ResourceRight $right
     *
     * @return ResourceLink
     */
    public function addResourceToCourseGroup(
        ResourceNode $resourceNode,
        Course $course,
        CGroupInfo $group,
        ResourceRight $right
    ) {
        $resourceLink = $this->addResourceToCourse(
            $resourceNode,
            $course,
            $right
        );
        $resourceLink->setGroup($group);
        $this->getEntityManager()->persist($resourceLink);

        return $resourceLink;
    }

    /**
     * @param ResourceNode  $resourceNode
     * @param Usergroup     $group
     * @param ResourceRight $right
     *
     * @return ResourceLink
     */
    public function addResourceToGroup(
        ResourceNode $resourceNode,
        Usergroup $group,
        ResourceRight $right
    ) {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->setUserGroup($group)
            ->addResourceRight($right);

        return $resourceLink;
    }

    /**
     * @param Course           $course
     * @param Tool             $tool
     * @param AbstractResource $parent
     *
     * @return ResourceLink
     */
    public function getResourceByCourse(Course $course, Tool $tool, AbstractResource $parent = null)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from('Chamilo\CoreBundle\Entity\Resource\ResourceNode', 'node')
            ->innerJoin('node.links', 'links')
            ->innerJoin(
                $this->getClassName(),
                'resource',
                Join::WITH,
                'resource.course = links.course AND resource.resourceNode = node.id'
            )
            ->where('node.tool = :tool')
            ->andWhere('links.course = :course')
            //->where('link.cId = ?', $course->getId())
            //->where('node.cId = 0')
            //->orderBy('node');
            ->setParameters(
                [
                    'tool' => $tool,
                    'course' => $course,
                ]
            );

        if ($parent !== null) {
            $query->andWhere('node.parent = :parentId');
            $query->setParameter('parentId', $parent->getResourceNode()->getId());
        } else {
            $query->andWhere('node.parent IS NULL');
        }

        $query = $query->getQuery();

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
     * @param string $tool
     *
     * @return Tool|null
     */
    public function getTool($tool)
    {
        return $this
            ->getEntityManager()
            ->getRepository('ChamiloCoreBundle:Tool')
            ->findOneBy(['name' => $tool]);
    }
}
