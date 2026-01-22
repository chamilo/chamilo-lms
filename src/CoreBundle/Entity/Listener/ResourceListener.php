<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Controller\Api\BaseResourceFileAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\EntityAccessUrlInterface;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceFormat;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceToRootInterface;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\ResourceWithAccessUrlInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CoreBundle\Traits\AccessUrlListenerTrait;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CDocument;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

use const JSON_THROW_ON_ERROR;
use const PATHINFO_EXTENSION;

class ResourceListener
{
    use AccessUrlListenerTrait;

    public function __construct(
        protected SlugifyInterface $slugify,
        protected ToolChain $toolChain,
        protected RequestStack $request,
        protected Security $security,
        protected TrackEDefaultRepository $trackEDefaultRepository
    ) {}

    /**
     * Only in creation.
     *
     * @throws Exception
     */
    public function prePersist(AbstractResource $resource, PrePersistEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $request = $this->request;

        /**
         * Current authenticated user (may be null in CLI/migrations).
         * Always initialize to avoid "Undefined variable" warnings.
         *
         * @var User|null $currentUser
         */
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            $currentUser = null;
        }

        // 1. Set AccessUrl.
        if ($resource instanceof ResourceWithAccessUrlInterface) {
            // Checking if this resource is connected with a AccessUrl.
            if (0 === $resource->getUrls()->count()) {
                // The AccessUrl was not added using $resource->addAccessUrl(),
                // Try getting the URL from the session bag if possible.
                $accessUrl = $this->getAccessUrl($em, $request);
                if (null === $accessUrl) {
                    throw new Exception('This resource needs an AccessUrl use $resource->addAccessUrl();');
                }
                $resource->addAccessUrl($accessUrl);
            }
        }

        // This will attach the resource to the main resource node root (For example a Course).
        if ($resource instanceof ResourceToRootInterface) {
            $accessUrl = $this->getAccessUrl($em, $request);
            $resource->setParent($accessUrl);
        }

        // 2. Set creator.
        // Priority order:
        //   1) Resource node creator (if explicitly provided)
        //   2) Resource creator (set via $resource->setCreator())
        //   3) Current authenticated user (Security token)
        // Then normalize to a managed reference to avoid duplicate INSERT on user.username.
        $creator = $resource->getResourceNodeCreator();
        if (!$creator instanceof User) {
            $creator = null;
        }

        if (null === $creator) {
            $explicitCreator = $resource->getCreator();
            if ($explicitCreator instanceof User) {
                $creator = $explicitCreator;
            }
        }

        if (null === $creator && $currentUser instanceof User) {
            $creator = $currentUser;
        }

        if (!$creator instanceof User) {
            throw new UserNotFoundException('User creator not found, use $resource->setCreator();');
        }

        // Ensure creator is managed by this EntityManager.
        if (!$em->contains($creator)) {
            $creatorId = $creator->getId();
            if (null === $creatorId) {
                throw new UserNotFoundException('Invalid creator user entity (missing ID).');
            }

            $creator = $em->getReference(User::class, (int) $creatorId);
        }

        // Ensure the resource itself has creator set (not only the ResourceNode).
        if (null === $resource->getCreator()) {
            $resource->setCreator($creator);
        }

        // 3. Set ResourceType.
        // @todo use static table instead of Doctrine
        $resourceTypeRepo = $em->getRepository(ResourceType::class);
        $entityClass = $eventArgs->getObject()::class;

        $name = $this->toolChain->getResourceTypeNameByEntity($entityClass);
        if (empty($name)) {
            return;
        }

        $resourceType = $resourceTypeRepo->findOneBy([
            'title' => $name,
        ]);

        if (null === $resourceType) {
            throw new InvalidArgumentException(\sprintf('ResourceType: "%s" not found for entity "%s"', $name, $entityClass));
        }

        // 4. Set ResourceNode parent.
        // Add resource directly to the resource node root (Example: a Course resource).
        $parentNode = null;
        if ($resource instanceof ResourceWithAccessUrlInterface) {
            $parentUrl = null;
            if ($resource->getUrls()->count() > 0) {
                $urlRelResource = $resource->getUrls()->first();
                if (!$urlRelResource instanceof EntityAccessUrlInterface) {
                    $msg = '$resource->getUrls() must return a Collection that implements EntityAccessUrlInterface';

                    throw new InvalidArgumentException($msg);
                }
                if (!$urlRelResource->getUrl()->hasResourceNode()) {
                    $msg = 'An item from the Collection $resource->getUrls() must implement EntityAccessUrlInterface.';

                    throw new InvalidArgumentException($msg);
                }
                $parentUrl = $urlRelResource->getUrl()->getResourceNode();
            }

            if (null === $parentUrl) {
                throw new InvalidArgumentException('The resource needs an AccessUrl: use $resource->addAccessUrl()');
            }
            $parentNode = $parentUrl;
        }

        // Reads the parentResourceNodeId parameter set in BaseResourceFileAction.php
        if ($resource->hasParentResourceNode()) {
            $nodeRepo = $em->getRepository(ResourceNode::class);
            $parent = $nodeRepo->find($resource->getParentResourceNode());
            if (null !== $parent) {
                $parentNode = $parent;
            }
        }

        if (null === $parentNode) {
            // Try getting the parent node from the resource.
            if (null !== $resource->getParent()) {
                $parentNode = $resource->getParent()->getResourceNode();
            }
        }

        // Last chance check parentResourceNodeId from request.
        if (null !== $request && null === $parentNode) {
            $currentRequest = $request->getCurrentRequest();
            if (null !== $currentRequest) {
                $resourceNodeIdFromRequest = $currentRequest->get('parentResourceNodeId');
                if (empty($resourceNodeIdFromRequest)) {
                    $contentData = $request->getCurrentRequest()->getContent();
                    $contentData = json_decode($contentData, true, 512, JSON_THROW_ON_ERROR);
                    $resourceNodeIdFromRequest = $contentData['parentResourceNodeId'] ?? '';
                }

                if (!empty($resourceNodeIdFromRequest)) {
                    $nodeRepo = $em->getRepository(ResourceNode::class);
                    $parent = $nodeRepo->find($resourceNodeIdFromRequest);
                    if (null !== $parent) {
                        $parentNode = $parent;
                    }
                }
            }
        }

        if (null === $parentNode && !$resource instanceof AccessUrl) {
            $msg = \sprintf('Resource %s needs a parent', $resource->getResourceName());

            throw new InvalidArgumentException($msg);
        }

        if ($resource instanceof PersonalFile) {
            // In CLI/migrations there is no authenticated user, so fallback to the parent node creator.
            if (null === $currentUser) {
                $currentUser = $parentNode?->getCreator();
            }

            if (!$currentUser instanceof User) {
                throw new UserNotFoundException('PersonalFile validation requires a user context (creator or parent node creator).');
            }

            $currentUserNode = $currentUser->getResourceNode();

            $valid = $parentNode->getCreator()->getUsername() === $currentUser->getUsername()
                || (null !== $currentUserNode && $parentNode->getId() === $currentUserNode->getId());

            if (!$valid) {
                $msg = \sprintf('User %s cannot add a file to another user', $currentUser->getUsername());

                throw new InvalidArgumentException($msg);
            }
        }

        // 4. Create ResourceNode for the Resource
        $resourceNode = (new ResourceNode())
            ->setCreator($creator)
            ->setResourceType($resourceType)
            ->setParent($parentNode)
        ;

        $txtTypes = [
            'events',
            'event_attachments',
            'illustrations',
            'links',
            'files',
            'courses',
            'users',
            'external_tools',
            'usergroups',
        ];
        $resourceFormatRepo = $em->getRepository(ResourceFormat::class);
        $formatName = (\in_array($name, $txtTypes, true) ? 'txt' : 'html');
        $resourceFormat = $resourceFormatRepo->findOneBy([
            'title' => $formatName,
        ]);
        if ($resourceFormat) {
            $resourceNode->setResourceFormat($resourceFormat);
        }

        $resource->setResourceNode($resourceNode);

        // Update resourceNode title from Resource.
        $this->updateResourceName($resource);

        BaseResourceFileAction::setLinks($resource, $em);

        // Upload File was set in BaseResourceFileAction.php
        if ($resource->hasUploadFile()) {
            $uploadedFile = $resource->getUploadFile();

            // File upload.
            if ($uploadedFile instanceof UploadedFile) {
                $resourceFile = (new ResourceFile())
                    ->setTitle($uploadedFile->getFilename())
                    ->setOriginalName($uploadedFile->getFilename())
                    ->setFile($uploadedFile)
                ;
                $resourceNode->addResourceFile($resourceFile);
                $em->persist($resourceNode);
            }
        }

        $resource->setResourceNode($resourceNode);

        // All resources should have a parent, except AccessUrl.
        if (!($resource instanceof AccessUrl) && null === $resourceNode->getParent()) {
            $message = \sprintf(
                'ResourceListener: Resource %s, has a resource node, but this resource node must have a parent',
                $resource->getResourceName()
            );

            throw new InvalidArgumentException($message);
        }

        if ($resource instanceof CCalendarEvent) {
            $this->addCCalendarEventGlobalLink($resource, $eventArgs);
        }
    }

    public function postPersist(AbstractResource $resource, PostPersistEventArgs $event): void
    {
        $resourceNode = $resource->getResourceNode();

        if ($resourceNode) {
            $this->trackEDefaultRepository->registerResourceEvent(
                $resourceNode,
                'creation',
                $this->security->getUser()?->getId()
            );
        }
    }

    public function postUpdate(AbstractResource $resource, PostUpdateEventArgs $event): void
    {
        $resourceNode = $resource->getResourceNode();

        if ($resourceNode) {
            $this->trackEDefaultRepository->registerResourceEvent(
                $resourceNode,
                'edition',
                $this->security->getUser()?->getId()
            );
        }
    }

    public function postRemove(AbstractResource $resource, PostRemoveEventArgs $event): void
    {
        $resourceNode = $resource->getResourceNode();

        if ($resourceNode) {
            $this->trackEDefaultRepository->registerResourceEvent(
                $resourceNode,
                'deletion',
                $this->security->getUser()?->getId()
            );
        }
    }

    /**
     * When updating a Resource.
     */
    public function preUpdate(AbstractResource $resource, PreUpdateEventArgs $eventArgs): void
    {
        $resourceNode = $resource->getResourceNode();
        $parentResourceNode = $resource->getParent()?->resourceNode;

        if ($parentResourceNode) {
            $resourceNode->setParent($parentResourceNode);
        }

        // error_log('Resource listener preUpdate');
        // $this->setLinks($resource, $eventArgs->getEntityManager());
    }

    public function updateResourceName(AbstractResource $resource): void
    {
        $resourceName = $resource->getResourceName();

        if (empty($resourceName)) {
            throw new InvalidArgumentException('Resource needs a name');
        }

        $extension = $this->slugify->slugify(pathinfo($resourceName, PATHINFO_EXTENSION));
        if (empty($extension)) {
            // $slug = $this->slugify->slugify($resourceName);
        }
        $resource->getResourceNode()->setTitle($resourceName);
    }

    private function addCCalendarEventGlobalLink(CCalendarEvent $event, PrePersistEventArgs $eventArgs): void
    {
        $currentRequest = $this->request->getCurrentRequest();

        if (null === $currentRequest) {
            return;
        }

        $type = $currentRequest->query->get('type');
        if (null === $type) {
            $content = $currentRequest->getContent();
            $params = json_decode($content, true);
            if (isset($params['isGlobal']) && 1 === (int) $params['isGlobal']) {
                $type = 'global';
            }
        }

        if ('global' === $type) {
            $em = $eventArgs->getObjectManager();
            $resourceNode = $event->getResourceNode();

            $globalLink = new ResourceLink();
            $globalLink->setCourse(null)
                ->setSession(null)
                ->setGroup(null)
                ->setUser(null)
            ;

            $alreadyHasGlobalLink = false;
            foreach ($resourceNode->getResourceLinks() as $existingLink) {
                if (null === $existingLink->getCourse() && null === $existingLink->getSession()
                    && null === $existingLink->getGroup() && null === $existingLink->getUser()) {
                    $alreadyHasGlobalLink = true;

                    break;
                }
            }

            if (!$alreadyHasGlobalLink) {
                $resourceNode->addResourceLink($globalLink);
                $em->persist($globalLink);
            }
        }
    }

    public function preRemove(AbstractResource $resource, LifecycleEventArgs $args): void
    {
        if (!$resource instanceof CDocument) {
            return;
        }

        $em = $args->getObjectManager();
        $docID = $resource->getIid();
        $em->createQuery('DELETE FROM Chamilo\CourseBundle\Entity\CLpItem i WHERE i.path = :path AND i.itemType = :type')
            ->setParameter('path', $docID)
            ->setParameter('type', 'document')
            ->execute()
        ;
    }
}
