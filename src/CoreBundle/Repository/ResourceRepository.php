<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Component\Resource\Template;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceRight;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\ToolChain;
use Chamilo\CourseBundle\Entity\CGroup;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
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
    protected $settings;
    protected $templates;
    protected $resourceType;

    /**
     * ResourceRepository constructor.
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManager $entityManager,
        RouterInterface $router,
        SlugifyInterface $slugify,
        ToolChain $toolChain,
        ResourceNodeRepository $resourceNodeRepository
    ) {
        $className = $this->getClassName();
        $this->repository = $entityManager->getRepository($className);
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
        $this->resourceNodeRepository = $resourceNodeRepository;
        $this->slugify = $slugify;
        $this->toolChain = $toolChain;
        $this->settings = new Settings();
        $this->templates = new Template();
    }

    public function getClassName()
    {
        $class = get_class($this);
        //Chamilo\CoreBundle\Repository\IllustrationRepository
        $class = str_replace('\\Repository\\', '\\Entity\\', $class);
        $class = str_replace('Repository', '', $class);
        if (false === class_exists($class)) {
            throw new \Exception("Repo: $class not found ");
        }

        return $class;
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

        return new $class();
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
        $formType = $this->getResourceFormType();

        if (null === $resource) {
            $className = $this->repository->getClassName();
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
        return $this->getRepository()->find($id, $lockMode, $lockVersion);
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

                //$originalBasename = \basename($resourceName, $originalExtension);
                /*$slug = sprintf(
                    '%s.%s',
                    $this->slugify->slugify($originalBasename),
                    $this->slugify->slugify($originalExtension)
                );*/

                $newOriginalName = sprintf('%s.%s', $resourceName, $originalExtension);
                $resourceFile->setOriginalName($newOriginalName);

                $em->persist($resourceFile);
            }
        } else {
            //$slug = $this->slugify->slugify($resourceName);
        }

        $resourceNode->setTitle($resourceName);
        //$resourceNode->setSlug($slug);

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

    public function addResourceToCourse(AbstractResource $resource, int $visibility, User $creator, Course $course, Session $session = null, CGroup $group = null, UploadedFile $file = null)
    {
        $resourceNode = $this->createNodeForResource($resource, $creator, $course->getResourceNode(), $file);

        $this->addResourceNodeToCourse($resourceNode, $visibility, $course, $session, $group);
    }

    public function addResourceToCourseWithParent(AbstractResource $resource, ResourceNode $parentNode, int $visibility, User $creator, Course $course, Session $session = null, CGroup $group = null, UploadedFile $file = null)
    {
        $resourceNode = $this->createNodeForResource($resource, $creator, $parentNode, $file);

        $this->addResourceNodeToCourse($resourceNode, $visibility, $course, $session, $group);
    }

    public function addResourceNodeToCourse(ResourceNode $resourceNode, int $visibility, Course $course, Session $session = null, CGroup $group = null, User $toUser = null): void
    {
        if (0 === $visibility) {
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
        $em->persist($resourceNode);
        $em->persist($link);
    }

    /**
     * @return ResourceType
     */
    public function getResourceType()
    {
        $name = $this->getResourceTypeName();
        $repo = $this->getEntityManager()->getRepository(ResourceType::class);
        $this->resourceType = $repo->findOneBy(['name' => $name]);

        return $this->resourceType;
    }

    public function getResourceTypeName(): string
    {
        return $this->toolChain->getResourceTypeNameFromRepository(get_class($this));
    }

    public function getResourcesByCourse(Course $course, Session $session = null, CGroup $group = null, ResourceNode $parentNode = null): QueryBuilder
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();
        $checker = $this->getAuthorizationChecker();
        $reflectionClass = $repo->getClassMetadata()->getReflectionClass();

        // Check if this resource type requires to load the base course resources when using a session
        $loadBaseSessionContent = $reflectionClass->hasProperty('loadCourseResourcesInSession');
        $resourceTypeName = $this->getResourceTypeName();

        $qb = $repo->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from($className, 'resource')
            ->innerJoin('resource.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->innerJoin('node.resourceType', 'type')
            //->innerJoin('links.course', 'course')
            ->leftJoin('node.resourceFile', 'file')

            ->where('type.name = :type')
            ->setParameter('type', $resourceTypeName)
            ->andWhere('links.course = :course')
            ->setParameter('course', $course)
            ->addSelect('node')
            ->addSelect('links')
            //->addSelect('course')
            ->addSelect('type')
            ->addSelect('file')
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
            // @todo Add start/end visibility restrictions.
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
        $resourceTypeName = $this->getResourceTypeName();

        $qb = $repo->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from($className, 'resource')
            ->innerJoin(
                'resource.resourceNode',
                'node'
            )
            ->innerJoin('node.resourceLinks', 'links')
            ->innerJoin('node.resourceType', 'type')
            ->where('type.name = :type')
            ->setParameter('type', $resourceTypeName)
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
                'resource.resourceNode',
                'node'
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

        return $qb;
    }

    public function getResourcesByCourseLinkedToUser(
        User $user,
        Course $course,
        Session $session = null,
        CGroup $group = null,
        ResourceNode $parentNode = null
    ): QueryBuilder {
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
        $resourceTypeName = $this->getResourceTypeName();

        $qb = $repo->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from($className, 'resource')
            ->innerJoin(
                'resource.resourceNode',
                'node'
            )
            ->innerJoin('node.resourceLinks', 'links')
            ->innerJoin('node.resourceType', 'type')
            ->where('type.name = :type')
            ->setParameter('type', $resourceTypeName)
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
        //return $this->getRepository()->findOneBy(['resourceNode' => $resourceNodeId]);
        //$className = $this->getClassName();
        /*var_dump(get_class($this->repository));
        var_dump(get_class($this->getRepository()));
        var_dump(get_class($this));*/
        //var_dump($className);
        // Include links
        $qb = $this->getRepository()->createQueryBuilder('resource')
            ->select('resource')
            ->addSelect('node')
            ->addSelect('links')
            //->addSelect('file')
            //->from($className, 'resource')
            ->innerJoin('resource.resourceNode', 'node')
        //    ->innerJoin('node.creator', 'userCreator')
            ->innerJoin('node.resourceLinks', 'links')
//            ->leftJoin('node.resourceFile', 'file')
            ->where('node.id = :id')
            ->setParameters(['id' => $resourceNodeId])
            //->addSelect('node')
        ;

        return $qb->getQuery()->getOneOrNullResult();
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

    public function getResourceFileDownloadUrl(AbstractResource $resource, array $extraParams = [], $referenceType = null): string
    {
        $extraParams['mode'] = 'download';

        return $this->getResourceFileUrl($resource, $extraParams, $referenceType);
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

    public function getResourceSettings(): Settings
    {
        return $this->settings;
    }

    public function getTemplates(): Template
    {
        return $this->templates;
    }

    /**
     * @param string $content
     *
     * @return bool
     */
    public function updateResourceFileContent(AbstractResource $resource, $content)
    {
        error_log('updateResourceFileContent');

        $resourceNode = $resource->getResourceNode();
        if ($resourceNode->hasResourceFile()) {
            error_log('has file');
            $resourceFile = $resourceNode->getResourceFile();
            if ($resourceFile) {
                error_log('$resourceFile');
                $title = $resource->getResourceName();
                $handle = tmpfile();
                fwrite($handle, $content);
                error_log($title);
                error_log($content);
                $meta = stream_get_meta_data($handle);
                $file = new UploadedFile($meta['uri'], $title, 'text/html', null, true);
                $resource->setUploadFile($file);

                return true;
            }
        }
        error_log('false');

        return false;
    }

    public function setResourceName(AbstractResource $resource, $title)
    {
        $resource->setResourceName($title);
        $resourceNode = $resource->getResourceNode();
        $resourceNode->setTitle($title);
        if ($resourceNode->hasResourceFile()) {
            //$resourceNode->getResourceFile()->getFile()->
            //$resourceNode->getResourceFile()->setName($title);
            //$resourceFile->setName($title);

            /*$fileName = $this->getResourceNodeRepository()->getFilename($resourceFile);
            error_log('$fileName');
            error_log($fileName);
            error_log($title);
            $this->getResourceNodeRepository()->getFileSystem()->rename($fileName, $title);
            $resourceFile->setName($title);
            $resourceFile->setOriginalName($title);*/
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
        $resourceName = $resource->getResourceName();
        $extension = $this->slugify->slugify(pathinfo($resourceName, PATHINFO_EXTENSION));

        if (empty($extension)) {
            $slug = $this->slugify->slugify($resourceName);
        } else {
            $originalExtension = pathinfo($resourceName, PATHINFO_EXTENSION);
            $originalBasename = \basename($resourceName, $originalExtension);
            $slug = sprintf('%s.%s', $this->slugify->slugify($originalBasename), $originalExtension);
        }

        $resourceNode = new ResourceNode();
        $resourceNode
            ->setTitle($resourceName)
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
