<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceRight;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ResourceRepository.
 */
class ResourceRepository extends EntityRepository
{
    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var FilesystemInterface
     */
    protected $fs;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * The entity class FQN.
     *
     * @var string
     */
    protected $className;

    /**
     * ResourceRepository constructor.
     *
     * @param EntityManager $entityManager
     * @param MountManager  $mountManager
     * @param string        $className
     */
    public function __construct(EntityManager $entityManager, MountManager $mountManager, string $className)
    {
        $this->repository = $entityManager->getRepository($className);
        // Flysystem mount name is saved in config/packages/oneup_flysystem.yaml
        $this->fs = $mountManager->getFilesystem('resources_fs');
    }

    /**
     * @return FilesystemInterface
     */
    public function getFileSystem()
    {
        return $this->fs;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->getRepository()->getEntityManager();
    }

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param mixed $id
     * @param null  $lockMode
     * @param null  $lockVersion
     *
     * @return AbstractResource|null
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return AbstractResource
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->getRepository()->findOneBy($criteria, $orderBy);
    }

    /**
     * @param AbstractResource $resource
     *
     * @return ResourceNode|mixed
     */
    /*public function getIllustration(AbstractResource $resource)
    {
        $node = $resource->getResourceNode();
        // @todo also filter by the resource type = Illustration
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('name', 'course_picture')
        );

        $illustration = $node->getChildren()->matching($criteria)->first();

        return $illustration;
    }*/

    /**
     * @param AbstractResource $resource
     * @param UploadedFile     $file
     *
     * @return ResourceFile
     */
    public function addFileToResource(AbstractResource $resource, UploadedFile $file)
    {
        $resourceNode = $resource->getResourceNode();

        if (!$resourceNode) {
            return false;
        }

        $resourceFile = $resourceNode->getResourceFile();
        if ($resourceFile === null) {
            $resourceFile = new ResourceFile();
        }

        $em = $this->getEntityManager();

        $resourceFile->setFile($file);
        $resourceFile->setName($resource->getResourceName());
        $em->persist($resourceFile);
        $resourceNode->setResourceFile($resourceFile);
        $em->persist($resourceNode);
        $em->flush();

        return $resourceFile;
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
        $em = $this->getEntityManager();

        $resourceType = $em->getRepository('ChamiloCoreBundle:Resource\ResourceType')->findOneBy(
            ['name' => $resource->getToolName()]
        );

        $resourceNode = new ResourceNode();
        $resourceNode
            ->setName($resource->getResourceName())
            ->setCreator($creator)
            ->setResourceType($resourceType)
        ;

        if ($parent !== null) {
            $resourceNode->setParent($parent->getResourceNode());
        }

        $resource->setResourceNode($resourceNode);

        $em->persist($resourceNode);
        $em->persist($resource);

        return $resourceNode;
    }

    /**
     * @param ResourceNode $resourceNode
     *
     * @return ResourceLink
     */
    public function addResourceToMe(ResourceNode $resourceNode): ResourceLink
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
     * @param ResourceNode $resourceNode
     * @param int          $visibility
     * @param Course       $course
     * @param Session      $session
     * @param CGroupInfo   $group
     */
    public function addResourceToCourse(ResourceNode $resourceNode, $visibility, $course, $session, $group): void
    {
        $visibility = (int) $visibility;
        if (empty($visibility)) {
            $visibility = ResourceLink::VISIBILITY_PUBLISHED;
        }

        $link = new ResourceLink();
        $link
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
            //->setUser($toUser)
            ->setResourceNode($resourceNode)
            ->setVisibility($visibility)
        ;

        $rights = [];
        switch ($visibility) {
            case ResourceLink::VISIBILITY_PENDING:
            case ResourceLink::VISIBILITY_DRAFT:
                $editorMask = ResourceNodeVoter::getEditorMask();
                $resourceRight = new ResourceRight();
                $resourceRight
                    ->setMask($editorMask)
                    ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                ;
                $rights[] = $resourceRight;
                break;
        }

        if (!empty($rights)) {
            foreach ($rights as $right) {
                $link->addResourceRight($right);
            }
        }

        $em = $this->getEntityManager();
        $em->persist($link);
    }

    /**
     * @param ResourceNode  $resourceNode
     * @param Course        $course
     * @param ResourceRight $right
     *
     * @return ResourceLink
     */
    public function addResourceToCourse2(ResourceNode $resourceNode, Course $course, ResourceRight $right): ResourceLink
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

    /**
     * @return mixed
     */
    public function create()
    {
        return new $this->className();
    }
}
