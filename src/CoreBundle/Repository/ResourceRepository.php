<?php

declare(strict_types=1);

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
use Chamilo\CoreBundle\Traits\NonResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Exception;
use LogicException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

/**
 * Extends EntityRepository is needed to process settings.
 */
abstract class ResourceRepository extends ServiceEntityRepository
{
    use NonResourceRepository;

    protected Settings $settings;
    protected Template $templates;
    protected ?ResourceType $resourceType = null;

    public function setSettings(Settings $settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    public function setTemplates(Template $templates): self
    {
        $this->templates = $templates;

        return $this;
    }

    public function getResourceSettings(): Settings
    {
        return $this->settings;
    }

    public function getTemplates(): Template
    {
        return $this->templates;
    }

    public function getClassName(): string
    {
        $class = static::class;
        //Chamilo\CoreBundle\Repository\Node\IllustrationRepository
        $class = str_replace('\\Repository\\', '\\Entity\\', $class);
        $class = str_replace('Repository', '', $class);
        if (!class_exists($class)) {
            throw new Exception(sprintf('Repo: %s not found ', $class));
        }

        return $class;
    }

    public function getCount(QueryBuilder $qb): int
    {
        $qb->select('count(resource)');
        $qb->setMaxResults(1);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return FormInterface
     */
    public function getForm(FormFactory $formFactory, ResourceInterface $resource = null, array $options = [])
    {
        $formType = $this->getResourceFormType();

        if (null === $resource) {
            $className = $this->repository->getClassName();
            $resource = new $className();
        }

        return $formFactory->create($formType, $resource, $options);
    }

    public function getResourceByResourceNode(ResourceNode $resourceNode): ?ResourceInterface
    {
        return $this->findOneBy([
            'resourceNode' => $resourceNode,
        ]);
    }

    /*public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->findOneBy($criteria, $orderBy);
    }*/

    /*public function updateResource(AbstractResource $resource)
    {
        $em = $this->getEntityManager();

        $resourceNode = $resource->getResourceNode();
        $resourceNode->setTitle($resource->getResourceName());

        $links = $resource->getResourceLinkEntityList();
        if ($links) {
            foreach ($links as $link) {
                $link->setResourceNode($resourceNode);

                $rights = [];
                switch ($link->getVisibility()) {
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
                $em->persist($link);
            }
        }

        $em->persist($resourceNode);
        $em->persist($resource);
        $em->flush();
    }*/

    public function create(AbstractResource $resource): void
    {
        $this->getEntityManager()->persist($resource);
        $this->getEntityManager()->flush();
    }

    public function update(AbstractResource $resource, bool $andFlush = true): void
    {
        if (!$resource->hasResourceNode()) {
            throw new Exception('Resource needs a resource node');
        }

        $resource->getResourceNode()->setUpdatedAt(new DateTime());
        $resource->getResourceNode()->setTitle($resource->getResourceName());
        $this->getEntityManager()->persist($resource);

        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }

    public function updateNodeForResource(ResourceInterface $resource): ResourceNode
    {
        $em = $this->getEntityManager();

        $resourceNode = $resource->getResourceNode();
        $resourceName = $resource->getResourceName();

        if ($resourceNode->hasResourceFile()) {
            $resourceFile = $resourceNode->getResourceFile();
            if (null !== $resourceFile) {
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
        }
        //$slug = $this->slugify->slugify($resourceName);

        $resourceNode->setTitle($resourceName);
        //$resourceNode->setSlug($slug);

        $em->persist($resourceNode);
        $em->persist($resource);

        $em->flush();

        return $resourceNode;
    }

    public function findCourseResourceByTitle(
        string $title,
        ResourceNode $parentNode,
        Course $course,
        Session $session = null,
        CGroup $group = null
    ): ?ResourceInterface {
        $qb = $this->getResourcesByCourse($course, $session, $group, $parentNode);
        $this->addTitleQueryBuilder($title, $qb);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findCourseResourcesByTitle(
        string $title,
        ResourceNode $parentNode,
        Course $course,
        Session $session = null,
        CGroup $group = null
    ) {
        $qb = $this->getResourcesByCourse($course, $session, $group, $parentNode);
        $this->addTitleQueryBuilder($title, $qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * @todo clean path
     */
    public function addFileFromPath(ResourceInterface $resource, string $fileName, string $path, bool $flush = true)
    {
        if (!empty($path) && file_exists($path) && !is_dir($path)) {
            $mimeType = mime_content_type($path);
            $file = new UploadedFile($path, $fileName, $mimeType, null, true);

            return $this->addFile($resource, $file, '', $flush);
        }

        return null;
    }

    public function addFileFromString(ResourceInterface $resource, string $fileName, string $mimeType, string $content, bool $flush = true): ?ResourceFile
    {
        $handle = tmpfile();
        fwrite($handle, $content);
        $meta = stream_get_meta_data($handle);
        $file = new UploadedFile($meta['uri'], $fileName, $mimeType, null, true);

        return $this->addFile($resource, $file, '', $flush);
    }

    public function addFileFromFileRequest(ResourceInterface $resource, string $fileKey, bool $flush = true): ?ResourceFile
    {
        $request = $this->getRequest();
        if ($request->files->has($fileKey)) {
            $file = $request->files->get($fileKey);
            if (null !== $file) {
                $resourceFile = $this->addFile($resource, $file);
                if ($flush) {
                    $this->getEntityManager()->flush();
                }

                return $resourceFile;
            }
        }

        return null;
    }

    public function addFile(ResourceInterface $resource, UploadedFile $file, string $description = '', bool $flush = false): ?ResourceFile
    {
        $resourceNode = $resource->getResourceNode();

        if (null === $resourceNode) {
            throw new LogicException('Resource node is null');
        }

        $resourceFile = $resourceNode->getResourceFile();
        if (null === $resourceFile) {
            $resourceFile = new ResourceFile();
        }

        $em = $this->getEntityManager();
        $resourceFile
            ->setFile($file)
            ->setDescription($description)
            ->setName($resource->getResourceName())
            ->setResourceNode($resourceNode)
        ;
        $em->persist($resourceFile);
        $resourceNode->setResourceFile($resourceFile);
        $em->persist($resourceNode);

        if ($flush) {
            $em->flush();
        }

        return $resourceFile;
    }

    public function getResourceType(): ?ResourceType
    {
        $name = $this->getResourceTypeName();
        $repo = $this->getEntityManager()->getRepository(ResourceType::class);
        $this->resourceType = $repo->findOneBy([
            'name' => $name,
        ]);

        return $this->resourceType;
    }

    public function getResourceTypeName(): string
    {
        return $this->toolChain->getResourceTypeNameFromRepository(static::class);
    }

    public function addVisibilityQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        $checker = $this->getAuthorizationChecker();
        $isAdmin =
            $checker->isGranted('ROLE_ADMIN') ||
            $checker->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        // Do not show deleted resources.
        $qb
            ->andWhere('links.visibility != :visibilityDeleted')
            ->setParameter('visibilityDeleted', ResourceLink::VISIBILITY_DELETED, Types::INTEGER)
        ;

        if (!$isAdmin) {
            $qb
                ->andWhere('links.visibility = :visibility')
                ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED, Types::INTEGER)
            ;
            // @todo Add start/end visibility restrictions.
        }

        return $qb;
    }

    public function addCourseQueryBuilder(Course $course, QueryBuilder $qb): QueryBuilder
    {
        $qb
            ->andWhere('links.course = :course')
            ->setParameter('course', $course)
        ;

        return $qb;
    }

    public function addCourseSessionGroupQueryBuilder(Course $course, Session $session = null, CGroup $group = null, QueryBuilder $qb = null): QueryBuilder
    {
        $reflectionClass = $this->getClassMetadata()->getReflectionClass();

        // Check if this resource type requires to load the base course resources when using a session
        $loadBaseSessionContent = $reflectionClass->hasProperty('loadCourseResourcesInSession');

        $this->addCourseQueryBuilder($course, $qb);

        if (null === $session) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('links.session'),
                    $qb->expr()->eq('links.session', 0)
                )
            );
        } elseif ($loadBaseSessionContent) {
            // Load course base content.
            $qb->andWhere('links.session = :session OR links.session IS NULL');
            $qb->setParameter('session', $session);
        } else {
            // Load only session resources.
            $qb->andWhere('links.session = :session');
            $qb->setParameter('session', $session);
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

    public function getResources(ResourceNode $parentNode = null): QueryBuilder
    {
        $resourceTypeName = $this->getResourceTypeName();

        $qb = $this->createQueryBuilder('resource')
            ->select('resource')
            ->innerJoin('resource.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->innerJoin('node.resourceType', 'type')
            ->leftJoin('node.resourceFile', 'file')
            ->where('type.name = :type')
            ->setParameter('type', $resourceTypeName, Types::STRING)
            ->addSelect('node')
            ->addSelect('links')
            ->addSelect('type')
            ->addSelect('file')
        ;

        if (null !== $parentNode) {
            $qb->andWhere('node.parent = :parentNode');
            $qb->setParameter('parentNode', $parentNode);
        }

        return $qb;
    }

    public function getResourcesByCourse(Course $course, Session $session = null, CGroup $group = null, ResourceNode $parentNode = null): QueryBuilder
    {
        $qb = $this->getResources($parentNode);
        $this->addVisibilityQueryBuilder($qb);
        $this->addCourseSessionGroupQueryBuilder($course, $session, $group, $qb);

        return $qb;
    }

    /**
     * Get resources only from the base course.
     */
    public function getResourcesByCourseOnly(Course $course, ResourceNode $parentNode = null): QueryBuilder
    {
        $qb = $this->getResources($parentNode);
        $this->addCourseQueryBuilder($course, $qb);
        $this->addVisibilityQueryBuilder($qb);

        return $qb;
    }

    public function getResourceByCreatorFromTitle(
        string $title,
        User $user,
        ResourceNode $parentNode
    ): ?ResourceInterface {
        $qb = $this->getResourcesByCreator($user, $parentNode);
        $this->addTitleQueryBuilder($title, $qb);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getResourcesByCreator(User $user, ResourceNode $parentNode = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('resource')
            ->select('resource')
            ->innerJoin('resource.resourceNode', 'node')
        ;

        if (null !== $parentNode) {
            $qb->andWhere('node.parent = :parentNode');
            $qb->setParameter('parentNode', $parentNode);
        }

        $this->addCreatorQueryBuilder($user, $qb);

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
        $qb->andWhere('node.creator = :user OR (links.user = :user OR links.user IS NULL)');
        $qb->setParameter('user', $user);

        return $qb;
    }

    public function getResourcesByLinkedUser(User $user, ResourceNode $parentNode = null): QueryBuilder
    {
        $qb = $this->getResources($parentNode);
        $qb
            ->andWhere('links.user = :user')
            ->setParameter('user', $user)
        ;

        $this->addVisibilityQueryBuilder($qb);

        return $qb;
    }

    public function getResourceFromResourceNode(int $resourceNodeId): ?ResourceInterface
    {
        $qb = $this->createQueryBuilder('resource')
            ->select('resource')
            ->addSelect('node')
            ->addSelect('links')
            ->innerJoin('resource.resourceNode', 'node')
        //    ->innerJoin('node.creator', 'userCreator')
            ->innerJoin('node.resourceLinks', 'links')
//            ->leftJoin('node.resourceFile', 'file')
            ->where('node.id = :id')
            ->setParameters([
                'id' => $resourceNodeId,
            ])
            //->addSelect('node')
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function delete(ResourceInterface $resource): void
    {
        $children = $resource->getResourceNode()->getChildren();
        foreach ($children as $child) {
            if ($child->hasResourceFile()) {
                $this->getEntityManager()->remove($child->getResourceFile());
            }
            $resourceNode = $this->getResourceFromResourceNode($child->getId());
            if (null !== $resourceNode) {
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
    public function hardDelete(AbstractResource $resource): void
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
        } catch (Throwable $throwable) {
            throw new FileNotFoundException($resource->getResourceName());
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
        return $this->getResourceNodeRepository()->getResourceFileUrl(
            $resource->getResourceNode(),
            $extraParams,
            $referenceType
        );
    }

    /**
     * @return bool
     */
    public function updateResourceFileContent(AbstractResource $resource, string $content)
    {
        error_log('updateResourceFileContent');

        $resourceNode = $resource->getResourceNode();
        if ($resourceNode->hasResourceFile()) {
            $resourceNode->setContent($content);
            $resourceNode->getResourceFile()->setSize(\strlen($content));
        }

        return false;
    }

    public function setResourceName(AbstractResource $resource, $title): void
    {
        if (!empty($title)) {
            $resource->setResourceName($title);
            $resourceNode = $resource->getResourceNode();
            $resourceNode->setTitle($title);
        }

        //if ($resourceNode->hasResourceFile()) {
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
        //}
    }

    /**
     * Change all links visibility to DELETED.
     */
    public function softDelete(AbstractResource $resource): void
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_DELETED);
    }

    public function setVisibilityPublished(AbstractResource $resource): void
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_PUBLISHED);
    }

    public function setVisibilityDeleted(AbstractResource $resource): void
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_DELETED);
    }

    public function setVisibilityDraft(AbstractResource $resource): void
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_DRAFT);
    }

    public function setVisibilityPending(AbstractResource $resource): void
    {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_PENDING);
    }

    public function addResourceNode(ResourceInterface $resource, User $creator, ResourceInterface $parentResource): ResourceNode
    {
        $parentResourceNode = $parentResource->getResourceNode();

        return $this->createNodeForResource($resource, $creator, $parentResourceNode);
    }

    /**
     * @todo remove this function and merge it with addResourceNode()
     */
    public function createNodeForResource(ResourceInterface $resource, User $creator, ResourceNode $parentNode, UploadedFile $file = null): ResourceNode
    {
        $em = $this->getEntityManager();

        $resourceType = $this->getResourceType();
        $resourceName = $resource->getResourceName();
        $extension = $this->slugify->slugify(pathinfo($resourceName, PATHINFO_EXTENSION));

        if (empty($extension)) {
            $slug = $this->slugify->slugify($resourceName);
        } else {
            $originalExtension = pathinfo($resourceName, PATHINFO_EXTENSION);
            $originalBasename = basename($resourceName, $originalExtension);
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

    /**
     * This is only used during installation for the special nodes (admin and AccessUrl).
     */
    public function createNodeForResourceWithNoParent(ResourceInterface $resource, User $creator): ResourceNode
    {
        $em = $this->getEntityManager();

        $resourceType = $this->getResourceType();
        $resourceName = $resource->getResourceName();
        $slug = $this->slugify->slugify($resourceName);
        $resourceNode = new ResourceNode();
        $resourceNode
            ->setTitle($resourceName)
            ->setSlug($slug)
            ->setCreator($creator)
            ->setResourceType($resourceType)
        ;
        $resource->setResourceNode($resourceNode);
        $em->persist($resourceNode);
        $em->persist($resource);

        return $resourceNode;
    }

    public function getTotalSpaceByCourse(Course $course, CGroup $group = null, Session $session = null): int
    {
        $qb = $this->createQueryBuilder('resource');
        $qb
            ->select('SUM(file.size) as total')
            ->innerJoin('resource.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'l')
            ->innerJoin('node.resourceFile', 'file')
            ->where('l.course = :course')
            ->andWhere('l.visibility <> :visibility')
            ->andWhere('file IS NOT NULL')
            ->setParameters(
                [
                    'course' => $course,
                    'visibility' => ResourceLink::VISIBILITY_DELETED,
                ]
            )
        ;

        if (null === $group) {
            $qb->andWhere('l.group IS NULL');
        } else {
            $qb
                ->andWhere('l.group = :group')
                ->setParameter('group', $group)
            ;
        }

        if (null === $session) {
            $qb->andWhere('l.session IS NULL');
        } else {
            $qb
                ->andWhere('l.session = :session')
                ->setParameter('session', $session)
            ;
        }

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    public function addTitleDecoration(AbstractResource $resource, Course $course, Session $session = null): string
    {
        if (null === $session) {
            return '';
        }

        $link = $resource->getFirstResourceLinkFromCourseSession($course, $session);
        if (null === $link) {
            return '';
        }

        return '<img title="'.$session->getName().'" src="/img/icons/22/star.png" />';
    }

    public function isGranted(string $subject, AbstractResource $resource): bool
    {
        return $this->getAuthorizationChecker()->isGranted($subject, $resource->getResourceNode());
    }

    /**
     * Changes the visibility of the children that matches the exact same link.
     */
    public function copyVisibilityToChildren(ResourceNode $resourceNode, ResourceLink $link): bool
    {
        $children = $resourceNode->getChildren();

        if (0 === $children->count()) {
            return false;
        }

        $em = $this->getEntityManager();

        /** @var ResourceNode $child */
        foreach ($children as $child) {
            if ($child->getChildren()->count() > 0) {
                $this->copyVisibilityToChildren($child, $link);
            }

            $links = $child->getResourceLinks();
            foreach ($links as $linkItem) {
                if ($linkItem->getUser() === $link->getUser() &&
                    $linkItem->getSession() === $link->getSession() &&
                    $linkItem->getCourse() === $link->getCourse() &&
                    $linkItem->getUserGroup() === $link->getUserGroup()
                ) {
                    $linkItem->setVisibility($link->getVisibility());
                    $em->persist($linkItem);
                }
            }
        }

        $em->flush();

        return true;
    }

    public function saveUpload(UploadedFile $file): ResourceInterface
    {
        throw new Exception('Implement saveUpload');
    }

    public function getResourceFormType(): string
    {
        throw new Exception('Implement getResourceFormType');
    }

    protected function getOrCreateQueryBuilder(QueryBuilder $qb = null, string $alias = 'resource'): QueryBuilder
    {
        return $qb ?: $this->createQueryBuilder($alias);
    }

    protected function addTitleQueryBuilder(?string $title, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        if (null === $title) {
            return $qb;
        }

        $qb
            ->andWhere('node.title = :title')
            ->setParameter('title', $title)
        ;

        return $qb;
    }

    protected function addCreatorQueryBuilder(?User $user, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        if (null === $user) {
            return $qb;
        }

        $qb
            ->andWhere('node.creator = :creator')
            ->setParameter('creator', $user)
        ;

        return $qb;
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
                    $criteria = [
                        'resourceNode' => $child,
                    ];
                    $childDocument = $this->findOneBy($criteria);
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
                    //$rights = [];
                    $resourceRight = new ResourceRight();
                    $resourceRight
                        ->setMask($editorMask)
                        ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                        ->setResourceLink($link)
                    ;
                    $link->addResourceRight($resourceRight);
                } else {
                    $link->setResourceRights(new ArrayCollection());
                }
                $em->persist($link);
            }
        }
        $em->flush();

        return true;
    }
}
