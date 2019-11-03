<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceRight;
use Chamilo\CoreBundle\Entity\Resource\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;

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

    /** @var RouterInterface */
    protected $router;

    protected $resourceNodeRepository;

    /**
     * ResourceRepository constructor.
     *
     * @param EntityManager   $entityManager
     * @param MountManager    $mountManager
     * @param RouterInterface $router
     * @param string          $className
     */
    public function __construct(EntityManager $entityManager, MountManager $mountManager, RouterInterface $router, string $className)
    {
        $this->repository = $entityManager->getRepository($className);
        // Flysystem mount name is saved in config/packages/oneup_flysystem.yaml @todo add it as a service.
        $this->fs = $mountManager->getFilesystem('resources_fs');
        $this->router = $router;
        $this->resourceNodeRepository = $entityManager->getRepository('ChamiloCoreBundle:Resource\ResourceNode');
    }

    /**
     * @return mixed
     */
    public function create()
    {
        return new $this->className();
    }

    /**
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    /**
     * @return EntityRepository
     */
    public function getResourceNodeRepository()
    {
        return $this->resourceNodeRepository;
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
    public function find($id, $lockMode = null, $lockVersion = null): ?AbstractResource
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return AbstractResource
     */
    public function findOneBy(array $criteria, array $orderBy = null): ?AbstractResource
    {
        return $this->getRepository()->findOneBy($criteria, $orderBy);
    }

    /**
     * @param ResourceNode $resourceNode
     * @param UploadedFile $file
     *
     * @return ResourceFile
     */
    public function addFile(ResourceNode $resourceNode, UploadedFile $file): ?ResourceFile
    {
        $resourceFile = $resourceNode->getResourceFile();
        if ($resourceFile === null) {
            $resourceFile = new ResourceFile();
        }

        $em = $this->getEntityManager();

        $resourceFile->setFile($file);
        $resourceFile->setName($resourceNode->getName());
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
    public function addResourceNode(AbstractResource $resource, User $creator, AbstractResource $parent = null): ResourceNode
    {
        $em = $this->getEntityManager();

        $resourceType = $this->getResourceType();

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
        $em->flush();

        return $resourceNode;
    }

    /**
     * @param AbstractResource $resource
     * @param int              $visibility
     * @param User             $creator
     * @param Course           $course
     * @param Session          $session
     * @param CGroupInfo       $group
     */
    public function addResourceToCourse(AbstractResource $resource, int $visibility, User $creator, Course $course, Session $session = null, CGroupInfo $group = null)
    {
        $node = $this->addResourceNode($resource, $creator, $course);
        $this->addResourceNodeToCourse($node, $visibility, $course, $session, $group);
    }

    /**
     * @param ResourceNode $resourceNode
     * @param int          $visibility
     * @param Course       $course
     * @param Session      $session
     * @param CGroupInfo   $group
     */
    public function addResourceNodeToCourse(ResourceNode $resourceNode, $visibility, $course, $session, $group): void
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
        $em->flush();
    }

    /**
     * @return ResourceType
     */
    public function getResourceType()
    {
        $em = $this->getEntityManager();
        $entityName = $this->getRepository()->getClassMetadata()->getReflectionClass()->getShortName();

        return $em->getRepository('ChamiloCoreBundle:Resource\ResourceType')->findOneBy(
            ['name' => $entityName]
        );
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
     * @param Course          $course
     * @param Session|null    $session
     * @param CGroupInfo|null $group
     *
     * @return QueryBuilder
     */
    public function getResourcesByCourse(Course $course, Session $session = null, CGroupInfo $group = null)
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();

        // Check if this resource type requires to load the base course resources when using a session
        $loadBaseSessionContent = $repo->getClassMetadata()->getReflectionClass()->hasProperty(
            'loadBaseCourseResourcesFromSession'
        );
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
            ->andWhere('resource.course = links.course')
            ->andWhere('links.course = :course')
            //->where('link.cId = ?', $course->getId())
            //->where('node.cId = 0')
            //->orderBy('node');
            ->setParameters(
                [
                    'type' => $type,
                    'course' => $course,
                ]
            );

        if ($session === null) {
            $qb->andWhere('links.session IS NULL');
        } else {
            if ($loadBaseSessionContent) {
                $qb->andWhere('links.session = :session OR links.session IS NULL');
                $qb->setParameter('session', $session);
            } else {
                $qb->andWhere('links.session = :session');
                $qb->setParameter('session', $session);
            }
        }

        if ($group === null) {
            $qb->andWhere('links.group IS NULL');
        }

        /*if ($parent !== null) {
            $qb->andWhere('node.parent = :parentId');
            $qb->setParameter('parentId', $parent->getResourceNode()->getId());
        } else {
            $qb->andWhere('node.parent = :parentId');
            $qb->setParameter('parentId', $course->getResourceNode());
        }*/

        /*$qb->setFirstResult();
        $qb->setMaxResults();
        $qb->orderBy();*/

        return $qb;

        $qb = $qb->getQuery();
        //var_dump($qb->getSQL());

        /*$qb = $this->getEntityManager()->createQueryBuilder()
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
        return $qb->getResult();
    }

    /**
     * @param string $tool
     *
     * @return Tool|null
     */
    private function getTool($tool)
    {
        return $this
            ->getEntityManager()
            ->getRepository('ChamiloCoreBundle:Tool')
            ->findOneBy(['name' => $tool]);
    }

    /**
     * @param AbstractResource $resource
     * @param int              $visibility
     * @param bool             $recursive
     */
    private function setLinkVisibility(AbstractResource $resource, $visibility, $recursive = true)
    {
        $resourceNode = $resource->getResourceNode();

        $em = $this->getEntityManager();
        if ($recursive) {
            $children = $resourceNode->getChildren();
            if (!empty($children)) {
                /** @var ResourceNode $child */
                foreach ($children as $child) {
                    $criteria = ['resourceNode' => $child];
                    $childDocument = $this->getRepository()->findOneBy($criteria);
                    if ($childDocument) {
                        $this->setLinkVisibility($childDocument, $visibility);
                    }
                }
            }
        }

        $links = $resourceNode->getResourceLinks();

        if (!empty($links)) {
            /** @var ResourceLink $link */
            foreach ($links as $link) {
                $link->setVisibility($visibility);
                if ($visibility === ResourceLink::VISIBILITY_DRAFT) {
                    $editorMask = ResourceNodeVoter::getEditorMask();
                    $rights = [];
                    $resourceRight = new ResourceRight();
                    $resourceRight
                        ->setMask($editorMask)
                        ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                        ->setResourceLink($link)
                    ;
                    $rights[] = $resourceRight;

                    if (!empty($rights)) {
                        $link->setResourceRight($rights);
                    }
                } else {
                    $link->setResourceRight([]);
                }
                $em->merge($link);
            }
        }
        $em->flush();
    }

    /**
     * Deletes several entities: AbstractResource (Ex: CDocument, CQuiz), ResourceNode,
     * ResourceLinks and ResourceFile (including files via Flysystem)
     *
     * @param AbstractResource $resource
     */
    public function hardDelete(AbstractResource $resource)
    {
        $em = $this->getEntityManager();
        $em->remove($resource);
        $em->flush();
    }

    /**
     * Change all links visibility to DELETED.
     *
     * @param AbstractResource $resource
     */
    public function softDelete(AbstractResource $resource)
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_DELETED);
    }
}
