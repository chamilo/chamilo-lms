<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceRight;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CreateUploadedFileHelper;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Traits\NonResourceRepository;
use Chamilo\CoreBundle\Traits\Repository\RepositoryQueryBuilderTrait;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryProxy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Exception;
use LogicException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

use const PATHINFO_EXTENSION;

/**
 * Extends Resource EntityRepository.
 *
 * @template T of object
 *
 * @template-extends ServiceEntityRepositoryProxy<T>
 */
abstract class ResourceRepository extends ServiceEntityRepository
{
    use NonResourceRepository;
    use RepositoryQueryBuilderTrait;

    protected ?ResourceType $resourceType = null;

    public function getCount(QueryBuilder $qb): int
    {
        $qb
            ->select('count(resource)')
            ->setMaxResults(1)
            ->setFirstResult(null)
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getResourceByResourceNode(ResourceNode $resourceNode): ?ResourceInterface
    {
        return $this->findOneBy([
            'resourceNode' => $resourceNode,
        ]);
    }

    public function create(AbstractResource $resource): void
    {
        $this->getEntityManager()->persist($resource);
        $this->getEntityManager()->flush();
    }

    public function update(AbstractResource|User $resource, bool $andFlush = true): void
    {
        if (!$resource->hasResourceNode()) {
            throw new Exception('Resource needs a resource node');
        }

        $em = $this->getEntityManager();

        $resource->getResourceNode()->setUpdatedAt(new DateTime());
        $resource->getResourceNode()->setTitle($resource->getResourceName());
        $em->persist($resource);

        if ($andFlush) {
            $em->flush();
        }
    }

    public function updateNodeForResource(ResourceInterface $resource): ResourceNode
    {
        $em = $this->getEntityManager();

        $resourceNode = $resource->getResourceNode();
        $resourceName = $resource->getResourceName();

        foreach ($resourceNode->getResourceFiles() as $resourceFile) {
            if (null !== $resourceFile) {
                $originalName = $resourceFile->getOriginalName();
                $originalExtension = pathinfo($originalName, PATHINFO_EXTENSION);

                // $originalBasename = \basename($resourceName, $originalExtension);
                /*$slug = sprintf(
                    '%s.%s',
                    $this->slugify->slugify($originalBasename),
                    $this->slugify->slugify($originalExtension)
                );*/

                $newOriginalName = \sprintf('%s.%s', $resourceName, $originalExtension);
                $resourceFile->setOriginalName($newOriginalName);

                $em->persist($resourceFile);
            }
        }
        // $slug = $this->slugify->slugify($resourceName);

        $resourceNode->setTitle($resourceName);
        // $resourceNode->setSlug($slug);

        $em->persist($resourceNode);
        $em->persist($resource);

        $em->flush();

        return $resourceNode;
    }

    public function findCourseResourceByTitle(
        string $title,
        ResourceNode $parentNode,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null
    ): ?ResourceInterface {
        $qb = $this->getResourcesByCourse($course, $session, $group, $parentNode);
        $this->addTitleQueryBuilder($title, $qb);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findCourseResourceBySlug(
        string $title,
        ResourceNode $parentNode,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null
    ): ?ResourceInterface {
        $qb = $this->getResourcesByCourse($course, $session, $group, $parentNode);
        $this->addSlugQueryBuilder($title, $qb);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find resources ignoring the visibility.
     */
    public function findCourseResourceBySlugIgnoreVisibility(
        string $title,
        ResourceNode $parentNode,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null
    ): ?ResourceInterface {
        $qb = $this->getResourcesByCourseIgnoreVisibility($course, $session, $group, $parentNode);
        $this->addSlugQueryBuilder($title, $qb);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return ResourceInterface[]
     */
    public function findCourseResourcesByTitle(
        string $title,
        ResourceNode $parentNode,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null
    ) {
        $qb = $this->getResourcesByCourse($course, $session, $group, $parentNode);
        $this->addTitleQueryBuilder($title, $qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * @todo clean path
     */
    public function addFileFromPath(ResourceInterface $resource, string $fileName, string $path, bool $flush = true): ?ResourceFile
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
        $file = CreateUploadedFileHelper::fromString($fileName, $mimeType, $content);

        return $this->addFile($resource, $file, '', $flush);
    }

    public function addFileFromFileRequest($resource, string $fileKey, bool $flush = true, ?int $index = null)
    {
        // Pick current request safely
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        if (!$request->files->has($fileKey)) {
            return null;
        }

        $value = $request->files->get($fileKey);
        $uploadedFile = null;

        if ($value instanceof UploadedFile) {
            // Single upload
            $uploadedFile = $value;
        } elseif (\is_array($value)) {
            // Multiple upload: select by index if provided
            if (null !== $index && isset($value[$index]) && $value[$index] instanceof UploadedFile) {
                $uploadedFile = $value[$index];
            } else {
                // Fallback: first valid file
                foreach ($value as $candidate) {
                    if ($candidate instanceof UploadedFile) {
                        $uploadedFile = $candidate;

                        break;
                    }
                }
            }
        }

        if (!$uploadedFile instanceof UploadedFile) {
            return null;
        }

        // Attach the file to the resource
        $resourceFile = $this->addFile($resource, $uploadedFile);

        if ($flush) {
            $this->_em->flush();
        }

        return $resourceFile;
    }

    public function addFile(ResourceInterface $resource, UploadedFile $file, string $description = '', bool $flush = false): ?ResourceFile
    {
        $resourceNode = $resource->getResourceNode();

        if (null === $resourceNode) {
            throw new LogicException('Resource node is null');
        }

        $em = $this->getEntityManager();

        $resourceFile = new ResourceFile();
        $resourceFile
            ->setFile($file)
            ->setDescription($description)
            ->setTitle($resource->getResourceName())
            ->setResourceNode($resourceNode)
        ;
        $resourceNode->addResourceFile($resourceFile);
        $em->persist($resourceFile);

        // Avoid re-persisting an already managed node (tiny but real win in migrations).
        if (!$em->contains($resourceNode)) {
            $em->persist($resourceNode);
        }

        if ($flush) {
            $em->flush();
        }

        return $resourceFile;
    }

    public function getResourceType(): ResourceType
    {
        $resourceTypeName = $this->toolChain->getResourceTypeNameByEntity($this->getClassName());
        $repo = $this->getEntityManager()->getRepository(ResourceType::class);

        return $repo->findOneBy([
            'title' => $resourceTypeName,
        ]);
    }

    public function addVisibilityQueryBuilder(?QueryBuilder $qb = null, bool $checkStudentView = false, bool $displayOnlyPublished = true): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        // TODO Avoid global assumption for a request, and inject
        // the request stack instead.
        $request = $this->getRequest();
        $sessionStudentView = null;
        if (null !== $request) {
            $sessionStudentView = $request->getSession()->get('studentview');
        }

        $checker = $this->getAuthorizationChecker();
        $isAdminOrTeacher =
            $checker->isGranted('ROLE_ADMIN')
            || $checker->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if ($displayOnlyPublished) {
            if (!$isAdminOrTeacher
                || ($checkStudentView && 'studentview' === $sessionStudentView)
            ) {
                $qb
                    ->andWhere('links.visibility = :visibility')
                    ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED, Types::INTEGER)
                ;
            }
        }

        // @todo Add start/end visibility restrictions.

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

    public function addSessionNullQueryBuilder(QueryBuilder $qb): QueryBuilder
    {
        return $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull('links.session'),
                $qb->expr()->eq('links.session', 0)
            )
        );
    }

    public function addSessionAndBaseContentQueryBuilder(Session $session, QueryBuilder $qb): QueryBuilder
    {
        return $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('links.session', ':session'),
                    $qb->expr()->isNull('links.session'),
                    $qb->expr()->eq('links.session', 0)
                )
            )
            ->setParameter('session', $session)
        ;
    }

    public function addSessionOnlyQueryBuilder(Session $session, QueryBuilder $qb): QueryBuilder
    {
        return $qb
            ->andWhere('links.session = :session')
            ->setParameter('session', $session)
        ;
    }

    public function addCourseSessionGroupQueryBuilder(
        ?Course $course = null,
        ?Session $session = null,
        ?CGroup $group = null,
        ?QueryBuilder $qb = null,
        ?bool $withBaseContentOverride = null
    ): QueryBuilder {
        $reflectionClass = $this->getClassMetadata()->getReflectionClass();

        // Default behavior based on interface (current logic).
        $defaultWithBaseContent = \in_array(
            ResourceShowCourseResourcesInSessionInterface::class,
            $reflectionClass->getInterfaceNames(),
            true
        );

        // Effective behavior can be overridden per call.
        $withBaseContent = $withBaseContentOverride ?? $defaultWithBaseContent;

        if ($course) {
            $this->addCourseQueryBuilder($course, $qb);
        }

        if (null === $session) {
            $this->addSessionNullQueryBuilder($qb);
        } elseif ($withBaseContent) {
            // Load course base content + session content.
            $this->addSessionAndBaseContentQueryBuilder($session, $qb);
        } else {
            // Load only session resources.
            $this->addSessionOnlyQueryBuilder($session, $qb);
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

    public function getResourceTypeName(): string
    {
        return $this->toolChain->getResourceTypeNameByEntity($this->getClassName());
    }

    public function getResources(?ResourceNode $parentNode = null): QueryBuilder
    {
        $resourceTypeName = $this->getResourceTypeName();

        $qb = $this->createQueryBuilder('resource')
            ->select('resource')
            ->innerJoin('resource.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->innerJoin('node.resourceType', 'type')
            ->leftJoin('node.resourceFiles', 'file')
            ->where('type.title = :type')
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

    public function getResourcesByCourse(
        ?Course $course = null,
        ?Session $session = null,
        ?CGroup $group = null,
        ?ResourceNode $parentNode = null,
        bool $displayOnlyPublished = true,
        bool $displayOrder = false,
        ?bool $withBaseContentOverride = null
    ): QueryBuilder {
        $qb = $this->getResources($parentNode);

        $this->addVisibilityQueryBuilder($qb, true, $displayOnlyPublished);

        $this->addCourseSessionGroupQueryBuilder(
            $course,
            $session,
            $group,
            $qb,
            $withBaseContentOverride
        );

        if ($displayOrder) {
            $qb->orderBy('links.displayOrder', 'ASC');
        }

        return $qb;
    }

    public function getResourcesBySession(
        ?Session $session = null,
        ?ResourceNode $parentNode = null,
        bool $displayOnlyPublished = true,
        bool $displayOrder = false,
        ?bool $withBaseContentOverride = null
    ): QueryBuilder {
        $qb = $this->getResources($parentNode);

        $this->addVisibilityQueryBuilder($qb, true, $displayOnlyPublished);

        $this->addCourseSessionGroupQueryBuilder(
            null,
            $session,
            null,
            $qb,
            $withBaseContentOverride
        );

        if ($displayOrder) {
            $qb->orderBy('links.displayOrder', 'ASC');
        }

        return $qb;
    }

    public function getResourcesByCourseIgnoreVisibility(Course $course, ?Session $session = null, ?CGroup $group = null, ?ResourceNode $parentNode = null): QueryBuilder
    {
        $qb = $this->getResources($parentNode);
        $this->addCourseSessionGroupQueryBuilder($course, $session, $group, $qb);

        return $qb;
    }

    /**
     * Get resources only from the base course.
     */
    public function getResourcesByCourseOnly(Course $course, ?ResourceNode $parentNode = null): QueryBuilder
    {
        $qb = $this->getResources($parentNode);
        $this->addCourseQueryBuilder($course, $qb);
        $this->addVisibilityQueryBuilder($qb);

        $qb->andWhere('links.session IS NULL');

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

    public function getResourcesByCreator(User $user, ?ResourceNode $parentNode = null): QueryBuilder
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
        ?Session $session = null,
        ?CGroup $group = null,
        ?ResourceNode $parentNode = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session, $group, $parentNode);
        $qb->andWhere('node.creator = :user OR (links.user = :user OR links.user IS NULL)');
        $qb->setParameter('user', $user);

        return $qb;
    }

    public function getResourcesByLinkedUser(User $user, ?ResourceNode $parentNode = null): QueryBuilder
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
            ->leftJoin('node.resourceLinks', 'links')
            ->where('node.id = :id')
            ->setParameters([
                'id' => $resourceNodeId,
            ])
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function delete(ResourceInterface $resource): void
    {
        $em = $this->getEntityManager();
        $children = $resource->getResourceNode()->getChildren();
        foreach ($children as $child) {
            foreach ($child->getResourceFiles() as $resourceFile) {
                $em->remove($resourceFile);
            }
            $resourceNode = $this->getResourceFromResourceNode($child->getId());
            if (null !== $resourceNode) {
                $this->delete($resourceNode);
            }
        }

        $em->remove($resource);
        $em->flush();
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
        $resourceNode = $resource->getResourceNode();

        if (null === $resourceNode) {
            throw new FileNotFoundException($resource->getResourceName());
        }

        try {
            return $this->resourceNodeRepository->getResourceNodeFileContent($resourceNode);
        } catch (Throwable $throwable) {
            // Fallback: editable text content (for resources stored without a physical file)
            if (method_exists($resourceNode, 'hasEditableTextContent')
                && $resourceNode->hasEditableTextContent()
                && method_exists($resourceNode, 'getEditableTextContent')
            ) {
                $editable = (string) $resourceNode->getEditableTextContent();
                if ('' !== trim($editable)) {
                    return $editable;
                }
            }

            // If there is no file, returning an empty string avoids fatal errors for non-file resources.
            if (method_exists($resourceNode, 'hasResourceFile') && !$resourceNode->hasResourceFile()) {
                return '';
            }

            throw new FileNotFoundException($resource->getResourceName(), 0, $throwable);
        }
    }

    public function getResourceNodeFileContent(ResourceNode $resourceNode): string
    {
        return $this->resourceNodeRepository->getResourceNodeFileContent($resourceNode);
    }

    public function getResourceFileDownloadUrl(AbstractResource $resource, array $extraParams = [], ?int $referenceType = null): string
    {
        $extraParams['mode'] = 'download';

        return $this->getResourceFileUrl($resource, $extraParams, $referenceType);
    }

    public function getResourceFileUrl(AbstractResource $resource, array $extraParams = [], ?int $referenceType = null): string
    {
        return $this->getResourceNodeRepository()->getResourceFileUrl(
            $resource->getResourceNode(),
            $extraParams,
            $referenceType
        );
    }

    public function updateResourceFileContent(AbstractResource $resource, string $content): bool
    {
        $resourceNode = $resource->getResourceNode();
        if ($resourceNode->hasResourceFile()) {
            $resourceNode->setContent($content);
            foreach ($resourceNode->getResourceFiles() as $resourceFile) {
                $resourceFile->setSize(\strlen($content));
            }

            return true;
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
    }

    public function toggleVisibilityPublishedDraft(
        AbstractResource $resource,
        ?Course $course = null,
        ?Session $session = null
    ): void {
        $firstLink = null;

        if (null !== $course) {
            $firstLink = $resource->getFirstResourceLinkFromCourseSession($course, $session);
        }

        $firstLink ??= $resource->getFirstResourceLink();

        if (null === $firstLink) {
            return;
        }

        if (ResourceLink::VISIBILITY_PUBLISHED === $firstLink->getVisibility()) {
            $this->setVisibilityDraft($resource, $course, $session);

            return;
        }

        if (ResourceLink::VISIBILITY_DRAFT === $firstLink->getVisibility()) {
            $this->setVisibilityPublished($resource, $course, $session);
        }
    }

    public function setVisibilityPublished(
        AbstractResource $resource,
        ?Course $course = null,
        ?Session $session = null,
    ): void {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_PUBLISHED, true, $course, $session);
    }

    public function setVisibilityDraft(
        AbstractResource $resource,
        ?Course $course = null,
        ?Session $session = null,
    ): void {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_DRAFT, true, $course, $session);
    }

    public function setVisibilityPending(
        AbstractResource $resource,
        ?Course $course = null,
        ?Session $session = null,
    ): void {
        $this->setLinkVisibility($resource, ResourceLink::VISIBILITY_PENDING, true, $course, $session);
    }

    public function addResourceNode(
        ResourceInterface $resource,
        User $creator,
        ResourceInterface $parentResource,
        ?ResourceType $resourceType = null,
    ): ResourceNode {
        $parentResourceNode = $parentResource->getResourceNode();

        return $this->createNodeForResource(
            $resource,
            $creator,
            $parentResourceNode,
            null,
            $resourceType,
        );
    }

    /**
     * @todo remove this function and merge it with addResourceNode()
     */
    public function createNodeForResource(
        ResourceInterface $resource,
        User $creator,
        ResourceNode $parentNode,
        ?UploadedFile $file = null,
        ?ResourceType $resourceType = null,
    ): ResourceNode {
        $em = $this->getEntityManager();

        $resourceType = $resourceType ?: $this->getResourceType();
        $resourceName = $resource->getResourceName();
        $extension = $this->slugify->slugify(pathinfo($resourceName, PATHINFO_EXTENSION));

        if (empty($extension)) {
            $slug = $this->slugify->slugify($resourceName);
        } else {
            $originalExtension = pathinfo($resourceName, PATHINFO_EXTENSION);
            $originalBasename = basename($resourceName, $originalExtension);
            $slug = \sprintf('%s.%s', $this->slugify->slugify($originalBasename), $originalExtension);
        }

        $resourceNode = new ResourceNode();
        $resourceNode
            ->setTitle($resourceName)
            ->setSlug($slug)
            ->setResourceType($resourceType)
        ;

        $creator->addResourceNode($resourceNode);

        $parentNode?->addChild($resourceNode);

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

    public function getTotalSpaceByCourse(Course $course, ?CGroup $group = null, ?Session $session = null): int
    {
        $qb = $this->createQueryBuilder('resource');
        $qb
            ->select('SUM(file.size) as total')
            ->innerJoin('resource.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'l')
            ->innerJoin('node.resourceFiles', 'file')
            ->where('l.course = :course')
            ->andWhere('file IS NOT NULL')
            ->setParameters(
                [
                    'course' => $course,
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

    public function addTitleDecoration(AbstractResource $resource, Course $course, ?Session $session = null): string
    {
        if (null === $session) {
            return '';
        }

        $link = $resource->getFirstResourceLinkFromCourseSession($course, $session);
        if (null === $link) {
            return '';
        }

        return '<img title="'.$session->getTitle().'" src="/img/icons/22/star.png" />';
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
                if ($linkItem->getUser() === $link->getUser()
                    && $linkItem->getSession() === $link->getSession()
                    && $linkItem->getCourse() === $link->getCourse()
                    && $linkItem->getUserGroup() === $link->getUserGroup()
                    && $linkItem->getGroup() === $link->getGroup()
                ) {
                    $linkItem->setVisibility($link->getVisibility());
                    $em->persist($linkItem);
                }
            }
        }

        $em->flush();

        return true;
    }

    protected function addSlugQueryBuilder(?string $slug, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        if (null === $slug) {
            return $qb;
        }

        $qb
            ->andWhere('node.slug = :slug OR node.slug LIKE :slug2')
            ->setParameter('slug', $slug) // normal slug = title
            ->setParameter('slug2', $slug.'%-%') // slug with a counter  = title-1
        ;

        return $qb;
    }

    protected function addTitleQueryBuilder(?string $title, ?QueryBuilder $qb = null): QueryBuilder
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

    protected function addCreatorQueryBuilder(?User $user, ?QueryBuilder $qb = null): QueryBuilder
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

    private function setLinkVisibility(
        AbstractResource $resource,
        int $visibility,
        bool $recursive = true,
        ?Course $course = null,
        ?Session $session = null,
        ?CGroup $group = null,
        ?User $user = null,
    ): bool {
        $resourceNode = $resource->getResourceNode();

        if (null === $resourceNode) {
            return false;
        }

        $em = $this->getEntityManager();
        if ($recursive) {
            $children = $resourceNode->getChildren();

            /** @var ResourceNode $child */
            foreach ($children as $child) {
                $criteria = [
                    'resourceNode' => $child,
                ];
                $childDocument = $this->findOneBy($criteria);
                if ($childDocument) {
                    $this->setLinkVisibility($childDocument, $visibility, true, $course, $session, $group, $user);
                }
            }
        }

        if ($resource instanceof ResourceShowCourseResourcesInSessionInterface && null !== $course) {
            $link = $resource->getFirstResourceLinkFromCourseSession($course, $session);

            if (!$link) {
                $resource->parentResource = $course;
                $resource->addCourseLink($course, $session);
            }

            $link = $resource->getFirstResourceLinkFromCourseSession($course, $session);
            $links = [$link];
        } else {
            $links = $resourceNode->getResourceLinks();
        }

        /** @var ResourceLink $link */
        foreach ($links as $link) {
            $link->setVisibility($visibility);
            if (ResourceLink::VISIBILITY_DRAFT === $visibility) {
                $editorMask = ResourceNodeVoter::getEditorMask();
                $resourceRight = (new ResourceRight())
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

        $em->flush();

        return true;
    }

    public function findByTitleAndParentResourceNode(string $title, int $parentResourceNodeId): ?AbstractResource
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.resourceNode', 'node')
            ->andWhere('d.title = :title')
            ->andWhere('node.parent = :parentResourceNodeId')
            ->setParameter('title', $title)
            ->setParameter('parentResourceNodeId', $parentResourceNodeId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findResourceByTitleInCourse(
        string $title,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null
    ): ?ResourceInterface {
        $qb = $this->getResourcesByCourse($course, $session, $group);

        $this->addTitleQueryBuilder($title, $qb);

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    protected function addOriginalNameQueryBuilder(?string $originalName, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        if (null === $originalName || '' === trim($originalName)) {
            return $qb;
        }

        $qb
            ->andWhere('file.originalName = :originalName')
            ->setParameter('originalName', $originalName)
        ;

        return $qb;
    }

    public function findResourceByOriginalNameInCourse(
        string $originalName,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null
    ): ?ResourceInterface {
        $qb = $this->getResourcesByCourseIgnoreVisibility($course, $session, $group);

        $this->addOriginalNameQueryBuilder($originalName, $qb);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findResourceByTitleInCourseIgnoreVisibility(
        string $title,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null
    ): ?ResourceInterface {
        $qb = $this->getResourcesByCourseIgnoreVisibility($course, $session, $group);

        $this->addTitleQueryBuilder($title, $qb);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
