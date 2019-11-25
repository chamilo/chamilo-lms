<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Row;
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
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var MountManager
     */
    protected $mountManager;
    protected $slugify;

    /**
     * ResourceRepository constructor.
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param EntityManager                 $entityManager
     * @param MountManager                  $mountManager
     * @param RouterInterface               $router
     * @param string                        $className
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManager $entityManager,
        MountManager $mountManager,
        RouterInterface $router,
        SlugifyInterface $slugify,
        string $className
    ) {
        $this->repository = $entityManager->getRepository($className);
        // Flysystem mount name is saved in config/packages/oneup_flysystem.yaml @todo add it as a service.
        $this->fs = $mountManager->getFilesystem('resources_fs');
        $this->mountManager = $mountManager;
        $this->router = $router;
        $this->resourceNodeRepository = $entityManager->getRepository('ChamiloCoreBundle:Resource\ResourceNode');
        $this->authorizationChecker = $authorizationChecker;
        $this->slugify = $slugify;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    public function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
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
     * @return ResourceNodeRepository
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
     * @param FormFactory $formFactory
     *
     * @return FormInterface
     */
    public function getForm(FormFactory $formFactory, AbstractResource $resource = null, $options = [])
    {
        $className = $this->repository->getClassName();
        $shortName = (new \ReflectionClass($className))->getShortName();

        $formType = str_replace('Entity\CDocument', 'Form\\Type', $className).'\\'.$shortName.'Type';

        if ($resource === null){
            $resource = new $className;
        }

        return $formFactory->create($formType, $resource, $options);
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
     * @return AbstractResource|null
     */
    public function findOneBy(array $criteria, array $orderBy = null): ?AbstractResource
    {
        return $this->getRepository()->findOneBy($criteria, $orderBy);
    }

    /**
     * @param AbstractResource  $resource
     * @param User              $creator
     * @param ResourceNode|null $parent
     *
     * @return ResourceNode
     */
    public function createNodeForResource(AbstractResource $resource, User $creator, ResourceNode $parent = null, UploadedFile $file = null): ResourceNode
    {
        $em = $this->getEntityManager();

        $resourceType = $this->getResourceType();

        $resourceNode = new ResourceNode();
        $resourceName = $resource->getResourceName();
        $extension = $this->slugify->slugify(pathinfo($resourceName, PATHINFO_EXTENSION));

        if (empty($extension)) {
            $slug = $this->slugify->slugify($resourceName);
        } else {
            $originalExtension = pathinfo($resourceName, PATHINFO_EXTENSION);
            $originalBasename = \basename($resourceName, $originalExtension);
            $slug = sprintf('%s.%s', $this->slugify->slugify($originalBasename), $originalExtension);
        }

        $resourceNode
            ->setSlug($slug)
            ->setCreator($creator)
            ->setResourceType($resourceType)
        ;

        if (null !== $parent) {
            $resourceNode->setParent($parent);
        }

        $resource->setResourceNode($resourceNode);
        $em->persist($resourceNode);
        $em->persist($resource);

        if (null !== $file) {
            $this->addFile($resource, $file);
        }

        return $resourceNode;
    }

    /**
     * @param AbstractResource $resource
     *
     * @return ResourceNode
     */
    public function updateNodeForResource(AbstractResource $resource): ResourceNode
    {
        $em = $this->getEntityManager();

        $resourceNode = $resource->getResourceNode();
        $resourceName = $resource->getResourceName();

        if ($resourceNode->hasResourceFile()) {
            $resourceFile = $resourceNode->getResourceFile();
            $originalName = $resourceFile->getOriginalName();
            $originalExtension = pathinfo($originalName, PATHINFO_EXTENSION);

            $originalBasename = \basename($resourceName, $originalExtension);
            $slug = sprintf(
                '%s.%s',
                $this->slugify->slugify($originalBasename),
                $this->slugify->slugify($originalExtension)
            );

            $newOriginalName = sprintf('%s.%s', $resourceName, $originalExtension);
            $resourceFile->setOriginalName($newOriginalName);

            $em->persist($resourceFile);
        } else {
            $slug = $this->slugify->slugify($resourceName);
        }

        $resourceNode->setSlug($slug);

        $em->persist($resourceNode);
        $em->persist($resource);

        $em->flush();

        return $resourceNode;
    }

    /**
     * @return ResourceFile|null
     */
    public function addFile(AbstractResource $resource, UploadedFile $file): ?ResourceFile
    {
        $resourceNode = $resource->getResourceNode();

        if (null === $resourceNode) {
            throw new \LogicException('Resource node is null');
        }

        $resourceFile = $resourceNode->getResourceFile();
        if (null === $resourceFile) {
            $resourceFile = new ResourceFile();
        }

        $em = $this->getEntityManager();

        $resourceFile->setFile($file);
        $resourceFile->setName($resource->getResourceName());
        $em->persist($resourceFile);

        $resourceNode->setResourceFile($resourceFile);
        $em->persist($resourceNode);

        return $resourceFile;
    }

    /**
     * @param AbstractResource      $resource
     * @param User                  $creator
     * @param AbstractResource|null $parent
     *
     * @return ResourceNode
     */
    public function addResourceNode(AbstractResource $resource, User $creator, AbstractResource $parent = null): ResourceNode
    {
        if (null !== $parent) {
            $parent = $parent->getResourceNode();
        }

        return $this->createNodeForResource($resource, $creator, $parent);
    }

    /**
     * @param AbstractResource $resource
     * @param int              $visibility
     * @param User             $creator
     * @param Course           $course
     * @param Session|null     $session
     * @param CGroupInfo|null  $group
     */
    public function addResourceToCourse(AbstractResource $resource, int $visibility, User $creator, Course $course, Session $session = null, CGroupInfo $group = null)
    {
        $node = $this->createNodeForResource($resource, $creator, $course->getResourceNode());

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
    }

    /**
     * @return ResourceType
     */
    public function getResourceType()
    {
        $em = $this->getEntityManager();
        $entityName = $this->getRepository()->getClassName();

        return $em->getRepository('ChamiloCoreBundle:Resource\ResourceType')->findOneBy(
            ['entityName' => $entityName]
        );
    }

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

    public function addResourceToUser(ResourceNode $resourceNode, User $toUser): ResourceLink
    {
        $resourceLink = $this->addResourceNodeToUser($resourceNode, $toUser);
        $this->getEntityManager()->persist($resourceLink);

        return $resourceLink;
    }

    public function addResourceNodeToUser(ResourceNode $resourceNode, User $toUser): ResourceLink
    {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
            ->setUser($toUser);

        return $resourceLink;
    }

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
     * @param array $userList User id list
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
    public function getResourcesByCourse(Course $course, Session $session = null, CGroupInfo $group = null, ResourceNode $parentNode = null)
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();
        $checker = $this->getAuthorizationChecker();

        // Check if this resource type requires to load the base course resources when using a session
        $loadBaseSessionContent = $repo->getClassMetadata()->getReflectionClass()->hasProperty(
            'loadCourseResourcesInSession'
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
            ->andWhere('links.course = :course')
            ->setParameters(
                [
                    'type' => $type,
                    'course' => $course,
                ]
            );

        $isAdmin = $checker->isGranted('ROLE_ADMIN') ||
            $checker->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if (false === $isAdmin) {
            $qb
                ->andWhere('links.visibility = :visibility')
                ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED)
            ;
            // @todo Add start/end visibility restrictrions
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

        if (null !== $parentNode) {
            $qb->andWhere('node.parent = :parentNode');
            $qb->setParameter('parentNode', $parentNode);
        }

        if (null === $group) {
            $qb->andWhere('links.group IS NULL');
        }

        //echo ($qb->getQuery()->getSQL());exit;

        return $qb;
    }

    /**
     * @param RowAction $action
     * @param Row       $row
     * @param Session   $session
     *
     * @return RowAction|null
     */
    public function rowCanBeEdited(RowAction $action, Row $row, Session $session = null): ?RowAction
    {
        if (null !== $session) {
            /** @var AbstractResource $entity */
            $entity = $row->getEntity();
            $hasSession = $entity->getResourceNode()->hasSession($session);
            if ($hasSession->count() > 0) {
                return $action;
            }

            return null;
        }

        return $action;
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
     * Deletes several entities: AbstractResource (Ex: CDocument, CQuiz), ResourceNode,
     * ResourceLinks and ResourceFile (including files via Flysystem).
     */
    public function hardDelete(AbstractResource $resource)
    {
        $em = $this->getEntityManager();
        $em->remove($resource);
        $em->flush();
    }

    /**
     * Change all links visibility to DELETED.
     */
    public function softDelete(AbstractResource $resource)
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_DELETED);
    }

    /**
     * @param AbstractResource $resource
     */
    public function setVisibilityPublished(AbstractResource $resource)
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_PUBLISHED);
    }

    /**
     * @param AbstractResource $resource
     */
    public function setVisibilityDraft(AbstractResource $resource)
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_DRAFT);
    }

    /**
     * @param AbstractResource $resource
     */
    public function setVisibilityPending(AbstractResource $resource)
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_PENDING);
    }

    /**
     * @param AbstractResource $resource
     * @param int              $visibility
     * @param bool             $recursive
     *
     * @return bool
     */
    private function setLinkVisibility(AbstractResource $resource, int $visibility, bool $recursive = true): bool
    {
        $resourceNode = $resource->getResourceNode();

        if (null === $resourceNode) {
            return false;
        }

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
                if (ResourceLink::VISIBILITY_DRAFT === $visibility) {
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
                $em->persist($link);
            }
        }
        $em->flush();

        return true;
    }

    /**
     * @param AbstractResource $resource
     *
     * @return string
     */
    public function getResourceFileContent(AbstractResource $resource): string
    {
        try {
            $resourceNode = $resource->getResourceNode();
            if ($resourceNode->hasResourceFile()) {
                $resourceFile = $resourceNode->getResourceFile();
                $fileName = $resourceFile->getFile()->getPathname();

                return $this->fs->read($fileName);
            }

            return '';
        } catch (\Throwable $exception) {
            throw new FileNotFoundException($resource);
        }
    }

    public function getResourceFileUrl(AbstractResource $resource, array $extraParams = []): string
    {
        try {
            $resourceNode = $resource->getResourceNode();
            if ($resourceNode->hasResourceFile()) {
                $params = [
                    'tool' => $resourceNode->getResourceType()->getTool(),
                    'type' => $resourceNode->getResourceType(),
                    'id' => $resourceNode->getId(),
                ];

                if (!empty($extraParams)) {
                    $params = array_merge($params, $extraParams);
                }

                return $this->router->generate('chamilo_core_resource_file', $params);
            }

            return '';
        } catch (\Throwable $exception) {
            throw new FileNotFoundException($resource);
        }
    }

    /**
     * @param AbstractResource $resource
     * @param string $content
     *
     * @return bool
     */
    public function updateResourceFileContent(AbstractResource $resource, $content)
    {
        try {
            $resourceNode = $resource->getResourceNode();
            if ($resourceNode->hasResourceFile()) {
                $resourceFile = $resourceNode->getResourceFile();
                $fileName = $resourceFile->getFile()->getPathname();

                $this->fs->update($fileName, $content);
                $size = $this->fs->getSize($fileName);
                $resource->setSize($size);
                $this->entityManager->persist($resource);

                return true;
            }

            return false;
        } catch (\Throwable $exception) {
        }
    }

}
