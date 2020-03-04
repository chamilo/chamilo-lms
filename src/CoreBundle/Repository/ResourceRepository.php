<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Row;
use Chamilo\CoreBundle\Component\Utils\ResourceSettings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Chamilo\CoreBundle\Entity\Resource\ResourceInterface;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceRight;
use Chamilo\CoreBundle\Entity\Resource\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\ToolChain;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class ResourceRepository.
 * Extends EntityRepository is needed to process settings.
 */
class ResourceRepository extends BaseEntityRepository
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

    /** @var ResourceNodeRepository */
    protected $resourceNodeRepository;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /** @var SlugifyInterface */
    protected $slugify;

    /** @var ToolChain */
    protected $toolChain;

    /**
     * ResourceRepository constructor.
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManager $entityManager,
        RouterInterface $router,
        SlugifyInterface $slugify,
        ToolChain $toolChain,
        ResourceNodeRepository $resourceNodeRepository,
        string $className
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->repository = $entityManager->getRepository($className);
        $this->router = $router;
        $this->resourceNodeRepository = $resourceNodeRepository;
        $this->slugify = $slugify;
        $this->toolChain = $toolChain;
    }

    public function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }

    /**
     * @return AbstractResource
     */
    public function create()
    {
        $class = $this->repository->getClassName();

        return new $class;
    }

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
     * @return FormInterface
     */
    public function getForm(FormFactory $formFactory, AbstractResource $resource = null, $options = [])
    {
        $className = $this->repository->getClassName();
        $shortName = (new \ReflectionClass($className))->getShortName();

        // @todo remove hardcode class loading
        $formType = 'Chamilo\CoreBundle\Form\Resource\\'.$shortName.'Type';
        if (null === $resource) {
            $resource = new $className();
        }

        return $formFactory->create($formType, $resource, $options);
    }

    /**
     * @param null $lockMode
     * @param null $lockVersion
     *
     * @return ResourceInterface
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return $this->getRepository()->find($id);
    }

    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->getRepository()->findOneBy($criteria, $orderBy);
    }

    public function updateNodeForResource(ResourceInterface $resource): ResourceNode
    {
        $em = $this->getEntityManager();

        $resourceNode = $resource->getResourceNode();
        $resourceName = $resource->getResourceName();

        if ($resourceNode->hasResourceFile()) {
            $resourceFile = $resourceNode->getResourceFile();
            if ($resourceFile) {
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
            }
        } else {
            $slug = $this->slugify->slugify($resourceName);
        }

        $resourceNode->setSlug($slug);

        $em->persist($resourceNode);
        $em->persist($resource);

        $em->flush();

        return $resourceNode;
    }

    public function addFile(ResourceInterface $resource, UploadedFile $file): ?ResourceFile
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

    public function addResourceNode(AbstractResource $resource, User $creator, AbstractResource $parent = null): ResourceNode
    {
        if (null !== $parent) {
            $parent = $parent->getResourceNode();
        }

        return $this->createNodeForResource($resource, $creator, $parent);
    }

    public function addResourceToCourse(AbstractResource $resource, int $visibility, User $creator, Course $course, Session $session = null, CGroupInfo $group = null, UploadedFile $file = null)
    {
        $node = $this->createNodeForResource($resource, $creator, $course->getResourceNode(), $file);

        $this->addResourceNodeToCourse($node, $visibility, $course, $session, $group);
    }

    public function addResourceToCourseWithParent(AbstractResource $resource, ResourceNode $parentNode, int $visibility, User $creator, Course $course, Session $session = null, CGroupInfo $group = null, UploadedFile $file = null)
    {
        $node = $this->createNodeForResource($resource, $creator, $parentNode, $file);

        $this->addResourceNodeToCourse($node, $visibility, $course, $session, $group);
    }

    public function addResourceNodeToCourse(ResourceNode $resourceNode, int $visibility, Course $course, Session $session = null, CGroupInfo $group = null, User $toUser = null): void
    {
        if (empty($visibility)) {
            $visibility = ResourceLink::VISIBILITY_PUBLISHED;
        }

        $link = new ResourceLink();
        $link
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
            ->setUser($toUser)
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

    public function addResourceToMe(ResourceNode $resourceNode): ResourceLink
    {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setResourceNode($resourceNode)
        ;

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
            ->setVisibility(ResourceLink::VISIBILITY_PUBLISHED)
            ->setUser($toUser);

        return $resourceLink;
    }

    public function addResourceToCourseGroup(
        ResourceNode $resourceNode,
        CGroupInfo $group
    ) {
        $exists = $resourceNode->getResourceLinks()->exists(function ($key, $element) use ($group) {
            if ($element->getGroup()) {
                return $group->getIid() == $element->getGroup()->getIid();
            }
        });

        if (false === $exists) {
            $resourceLink = new ResourceLink();
            $resourceLink
                ->setResourceNode($resourceNode)
                ->setGroup($group)
                ->setVisibility(ResourceLink::VISIBILITY_PUBLISHED)
            ;
            $this->getEntityManager()->persist($resourceLink);

            return $resourceLink;
        }
    }

    /*public function addResourceToSession(
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
    }*/

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
            $userRepo = $em->getRepository('ChamiloUserBundle:User');
            foreach ($userList as $userId) {
                $toUser = $userRepo->find($userId);
                $resourceLink = $this->addResourceNodeToUser($resourceNode, $toUser);
                $em->persist($resourceLink);
            }
        }
    }

    /**
     * @return ResourceType
     */
    public function getResourceType()
    {
        $em = $this->getEntityManager();
        $service = get_class($this);
        $name = $this->toolChain->getResourceTypeNameFromRepository($service);
        $repo = $em->getRepository('ChamiloCoreBundle:Resource\ResourceType');

        return $repo->findOneBy(['name' => $name]);
    }

    public function getResourcesByCourse(Course $course, Session $session = null, CGroupInfo $group = null, ResourceNode $parentNode = null): QueryBuilder
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

        $isAdmin = $checker->isGranted('ROLE_ADMIN') ||
            $checker->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        // Do not show deleted resources
        $qb
            ->andWhere('links.visibility != :visibilityDeleted')
            ->setParameter('visibilityDeleted', ResourceLink::VISIBILITY_DELETED)
        ;

        if (false === $isAdmin) {
            $qb
                ->andWhere('links.visibility = :visibility')
                ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED)
            ;
            // @todo Add start/end visibility restrictrions
        }

        if (null === $session) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('links.session'),
                    $qb->expr()->eq('links.session', 0)
                )
            );
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
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('links.group'),
                    $qb->expr()->eq('links.group', 0)
                )
            );
        } else {
            $qb->andWhere('links.group = :group');
            $qb->setParameter('group', $group);
        }

        return $qb;
    }

    public function getResourcesByCourseOnly(Course $course, ResourceNode $parentNode = null)
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();
        $checker = $this->getAuthorizationChecker();
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

        $isAdmin = $checker->isGranted('ROLE_ADMIN') ||
            $checker->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        // Do not show deleted resources
        $qb
            ->andWhere('links.visibility != :visibilityDeleted')
            ->setParameter('visibilityDeleted', ResourceLink::VISIBILITY_DELETED)
        ;

        if (false === $isAdmin) {
            $qb
                ->andWhere('links.visibility = :visibility')
                ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED)
            ;
            // @todo Add start/end visibility restrictrions
        }

        if (null !== $parentNode) {
            $qb->andWhere('node.parent = :parentNode');
            $qb->setParameter('parentNode', $parentNode);
        }

        return $qb;
    }

    /**
     * @return QueryBuilder
     */
    public function getResourcesByCreator(User $user, ResourceNode $parentNode = null)
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

        $qb->andWhere('node.creator = :creator');
        $qb->setParameter('creator', $user);
        //var_dump($qb->getQuery()->getSQL(), $parentNode->getId());exit;

        return $qb;
    }

    public function getResourcesByCourseLinkedToUser(User $user, Course $course, Session $session = null, CGroupInfo $group = null, ResourceNode $parentNode = null): QueryBuilder
    {
        $qb = $this->getResourcesByCourse($course, $session, $group, $parentNode);

        $qb
            ->andWhere('links.user = :user')
            ->setParameter('user', $user);

        return $qb;
    }

    public function getResourcesByLinkedUser(User $user, ResourceNode $parentNode = null): QueryBuilder
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();
        $checker = $this->getAuthorizationChecker();
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
            ->andWhere('links.user = :user')
            ->setParameter('user', $user)
        ;

        $isAdmin = $checker->isGranted('ROLE_ADMIN') ||
            $checker->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        // Do not show deleted resources
        $qb
            ->andWhere('links.visibility != :visibilityDeleted')
            ->setParameter('visibilityDeleted', ResourceLink::VISIBILITY_DELETED)
        ;

        if (false === $isAdmin) {
            $qb
                ->andWhere('links.visibility = :visibility')
                ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED)
            ;
            // @todo Add start/end visibility restrictrions
        }

        if (null !== $parentNode) {
            $qb->andWhere('node.parent = :parentNode');
            $qb->setParameter('parentNode', $parentNode);
        }

        return $qb;
    }

    public function getResourceFromResourceNode(int $resourceNodeId): ?AbstractResource
    {
        return $this->getRepository()->findOneBy(['resourceNode' => $resourceNodeId]);
    }

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

    public function delete(AbstractResource $resource)
    {
        $children = $resource->getResourceNode()->getChildren();
        foreach ($children as $child) {
            if ($child->hasResourceFile()) {
                $this->getEntityManager()->remove($child->getResourceFile());
            }
            $resourceNode = $this->getResourceFromResourceNode($child->getId());
            if ($resourceNode) {
                $this->delete($resourceNode);
            }
        }
        $this->getEntityManager()->remove($resource);
        $this->getEntityManager()->flush();
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

    public function getResourceFileContent(AbstractResource $resource): string
    {
        try {
            $resourceNode = $resource->getResourceNode();

            return $this->resourceNodeRepository->getResourceNodeFileContent($resourceNode);
        } catch (\Throwable $exception) {
            throw new FileNotFoundException($resource);
        }
    }

    public function getResourceNodeFileContent(ResourceNode $resourceNode): string
    {
        return $this->resourceNodeRepository->getResourceNodeFileContent($resourceNode);
    }

    public function getResourceNodeFileStream(ResourceNode $resourceNode)
    {
        return $this->resourceNodeRepository->getResourceNodeFileStream($resourceNode);
    }

    public function getResourceFileUrl(AbstractResource $resource, array $extraParams = [], $referenceType = null): string
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

                $referenceType = $referenceType ?? UrlGeneratorInterface::ABSOLUTE_PATH;

                return $this->router->generate('chamilo_core_resource_view_file', $params, $referenceType);
            }

            return '';
        } catch (\Throwable $exception) {
            throw new FileNotFoundException($resource);
        }
    }

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

    /**
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
                if ($resourceFile) {
                    $fileName = $this->getResourceNodeRepository()->getFilename($resourceFile);
                    $this->getResourceNodeRepository()->getFileSystem()->update($fileName, $content);
                    //$size = $this->getResourceNodeRepository()->getSize($resourceNode);

                    if ($resource instanceof CDocument) {
                        //$resource->setSize($size);
                    }
                    $this->entityManager->persist($resource);

                    return true;
                }
            }

            return false;
        } catch (\Throwable $exception) {
        }
    }

    /**
     * Change all links visibility to DELETED.
     */
    public function softDelete(AbstractResource $resource)
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_DELETED);
    }

    public function setVisibilityPublished(AbstractResource $resource)
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_PUBLISHED);
    }

    public function setVisibilityDraft(AbstractResource $resource)
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_DRAFT);
    }

    public function setVisibilityPending(AbstractResource $resource)
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_PENDING);
    }

    public function createNodeForResource(ResourceInterface $resource, User $creator, ResourceNode $parentNode = null, UploadedFile $file = null): ResourceNode
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

        if (null !== $parentNode) {
            $resourceNode->setParent($parentNode);
        }

        $resource->setResourceNode($resourceNode);
        $em->persist($resourceNode);
        $em->persist($resource);

        if (null !== $file) {
            $this->addFile($resource, $file);
        }

        return $resourceNode;
    }

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
}
