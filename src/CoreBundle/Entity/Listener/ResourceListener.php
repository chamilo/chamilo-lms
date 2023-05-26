<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Controller\Api\BaseResourceFileAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\EntityAccessUrlInterface;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceToRootInterface;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\ResourceWithAccessUrlInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CoreBundle\Traits\AccessUrlListenerTrait;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Security;

class ResourceListener
{
    use AccessUrlListenerTrait;

    public function __construct(
        protected SlugifyInterface $slugify,
        protected ToolChain $toolChain,
        protected RequestStack $request,
        protected Security $security
    ) {
    }

    /**
     * Only in creation.
     *
     * @throws Exception
     */
    public function prePersist(AbstractResource $resource, LifecycleEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $request = $this->request;

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
        // Check if creator was set with $resource->setCreator()
        $creator = $resource->getResourceNodeCreator();

        $currentUser = null;
        if (null === $creator) {
            // Get the creator from the current request.
            /** @var User|null $currentUser */
            $currentUser = $this->security->getUser();
            if (null !== $currentUser) {
                $creator = $currentUser;
            }

            // Check if user has a resource node.
            if ($resource->hasResourceNode() && null !== $resource->getCreator()) {
                $creator = $resource->getCreator();
            }
        }

        if (null === $creator) {
            throw new UserNotFoundException('User creator not found, use $resource->setCreator();');
        }

        // 3. Set ResourceType.
        // @todo use static table instead of Doctrine
        $resourceTypeRepo = $em->getRepository(ResourceType::class);
        $entityClass = $eventArgs->getObject()::class;

        $name = $this->toolChain->getResourceTypeNameByEntity($entityClass);

        $resourceType = $resourceTypeRepo->findOneBy([
            'name' => $name,
        ]);

        if (null === $resourceType) {
            throw new InvalidArgumentException(sprintf('ResourceType: "%s" not found for entity "%s"', $name, $entityClass));
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
                throw new InvalidArgumentException(('The resource needs an AccessUrl: use $resource->addAccessUrl()'));
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
            $msg = sprintf('Resource %s needs a parent', $resource->getResourceName());

            throw new InvalidArgumentException($msg);
        }

        if ($resource instanceof PersonalFile) {
            $valid = $parentNode->getCreator()->getUsername() === $currentUser->getUsername() ||
                     $parentNode->getId() === $currentUser->getResourceNode()->getId();

            if (!$valid) {
                $msg = sprintf('User %s cannot add a file to another user', $currentUser->getUsername());

                throw new InvalidArgumentException($msg);
            }
        }

        // 4. Create ResourceNode for the Resource
        $resourceNode = (new ResourceNode())
            ->setCreator($creator)
            ->setResourceType($resourceType)
            ->setParent($parentNode)
        ;
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
                    ->setName($uploadedFile->getFilename())
                    ->setOriginalName($uploadedFile->getFilename())
                    ->setFile($uploadedFile)
                ;
                $em->persist($resourceFile);
                $resourceNode->setResourceFile($resourceFile);
            }
        }

        $resource->setResourceNode($resourceNode);

        // All resources should have a parent, except AccessUrl.
        if (!($resource instanceof AccessUrl) && null === $resourceNode->getParent()) {
            $message = sprintf(
                'ResourceListener: Resource %s, has a resource node, but this resource node must have a parent',
                $resource->getResourceName()
            );

            throw new InvalidArgumentException($message);
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

        //error_log('Resource listener preUpdate');
        //$this->setLinks($resource, $eventArgs->getEntityManager());
    }

    public function postUpdate(AbstractResource $resource, LifecycleEventArgs $eventArgs): void
    {
        //error_log('resource listener postUpdate');
        //$em = $eventArgs->getEntityManager();
        //$this->updateResourceName($resource, $resource->getResourceName(), $em);
    }

    public function updateResourceName(AbstractResource $resource): void
    {
        $resourceName = $resource->getResourceName();

        if (empty($resourceName)) {
            throw new InvalidArgumentException('Resource needs a name');
        }

        $extension = $this->slugify->slugify(pathinfo($resourceName, PATHINFO_EXTENSION));
        if (empty($extension)) {
            //$slug = $this->slugify->slugify($resourceName);
        }
        /*$originalExtension = pathinfo($resourceName, PATHINFO_EXTENSION);
        $originalBasename = \basename($resourceName, $originalExtension);
        $slug = sprintf('%s.%s', $this->slugify->slugify($originalBasename), $originalExtension);*/
        $resource->getResourceNode()->setTitle($resourceName);
    }
}
