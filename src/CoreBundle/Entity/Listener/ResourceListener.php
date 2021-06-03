<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\EntityAccessUrl;
use Chamilo\CoreBundle\Entity\EntityAccessUrlInterface;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceRight;
use Chamilo\CoreBundle\Entity\ResourceToRootInterface;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\ResourceWithAccessUrlInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\ToolChain;
use Chamilo\CourseBundle\Entity\CGroup;
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
    protected SlugifyInterface $slugify;
    protected Security $security;
    protected ToolChain $toolChain;
    protected RequestStack $request;
    protected ?AccessUrl $accessUrl;

    public function __construct(
        SlugifyInterface $slugify,
        ToolChain $toolChain,
        RequestStack $request,
        Security $security
    ) {
        $this->slugify = $slugify;
        $this->security = $security;
        $this->toolChain = $toolChain;
        $this->request = $request;
        $this->accessUrl = null;
    }

    /**
     * Only in creation.
     */
    public function prePersist(AbstractResource $resource, LifecycleEventArgs $event)
    {
        error_log('Resource listener prePersist for obj: '.\get_class($resource));
        $em = $event->getEntityManager();
        $request = $this->request;

        if ($resource instanceof ResourceWithAccessUrlInterface) {
            if (0 === $resource->getUrls()->count()) {
                throw new Exception('This resource needs an AccessUrl use $resource->addAccessUrl();');
            }
        }

        if ($resource->hasResourceNode()) {
            // This will attach the resource to the main resource node root (For example a Course).
            /*if ($resource instanceof ResourceToRootInterface) {
                $url = $this->getAccessUrl($em);
                $resource->getResourceNode()->setParent($url->getResourceNode());
            }
            error_log('resource has already a resource node. Do nothing');
            // Do not override resource node, it's already added.
            return true;*/
        }

        // Check if creator is set with $resource->setCreator()
        $creator = $resource->getResourceNodeCreator();
        if (null === $creator) {
            /** @var User|null $creator */
            $defaultCreator = $this->security->getUser();
            if (null !== $defaultCreator) {
                $creator = $defaultCreator;
            }

            // Check if user has a resource node.
            if ($resource->hasResourceNode() && null !== $resource->getCreator()) {
                $creator = $resource->getCreator();
            }
        }

        if (null === $creator) {
            throw new UserNotFoundException('User creator not found, use $resource->setCreator();');
        }

        $resourceNode = new ResourceNode();
        $resource->setResourceNode($resourceNode);
        $this->updateResourceName($resource);
        $resourceName = $resource->getResourceName();

        // @todo use static table instead of Doctrine
        $repo = $em->getRepository(ResourceType::class);
        $entityClass = \get_class($event->getEntity());
        $repoClass = str_replace('Entity', 'Repository', $entityClass).'Repository';
        if (strpos($repoClass, 'CoreBundle')) {
            $repoClass = str_replace('Entity', 'Repository\Node', $entityClass).'Repository';
        }
        $name = $this->toolChain->getResourceTypeNameFromRepository($repoClass);
        $resourceType = $repo->findOneBy([
            'name' => $name,
        ]);

        if (null === $resourceType) {
            throw new InvalidArgumentException(sprintf('ResourceType: %s not found', $name));
        }

        $resourceNode
            ->setCreator($creator)
            ->setResourceType($resourceType)
        ;

        // Add resource directly to the resource node root (Example: for a course resource).
        if ($resource instanceof ResourceWithAccessUrlInterface) {
            $parentUrl = null;
            if ($resource->getUrls()->count() > 0) {
                $urlRelResource = $resource->getUrls()->first();
                if (!$urlRelResource instanceof EntityAccessUrlInterface) {
                    throw new InvalidArgumentException('$resource->getUrls() must return a list of objects that implements the EntityAccessUrl interface');
                }
                if (!$urlRelResource->getUrl()->hasResourceNode()) {
                    throw new InvalidArgumentException('A child from $resource->getUrls() must implement EntityAccessUrl interface and return a valid AccessUrl with a ResourceNode');
                }
                $parentUrl = $urlRelResource->getUrl()->getResourceNode();
            }

            if (null === $parentUrl) {
                throw new InvalidArgumentException(('The resource needs an AccessUrl: use $resource->addAccessUrl()'));
            }
            $resourceNode->setParent($parentUrl);
        }

        if ($resource->hasParentResourceNode()) {
            $nodeRepo = $em->getRepository(ResourceNode::class);
            $parent = $nodeRepo->find($resource->getParentResourceNode());
            $resourceNode->setParent($parent);
        }

        if ($resource->hasUploadFile()) {
            error_log('hasUploadFile');
            // @todo check CreateResourceNodeFileAction
            /** @var File $uploadedFile */
            $uploadedFile = $request->getCurrentRequest()->files->get('uploadFile');

            if (empty($uploadedFile)) {
                $content = $request->getCurrentRequest()->get('contentFile');
                $title = $resourceName.'.html';
                $handle = tmpfile();
                fwrite($handle, $content);
                $meta = stream_get_meta_data($handle);
                $uploadedFile = new UploadedFile($meta['uri'], $title, 'text/html', null, true);
            }

            // File upload
            if ($uploadedFile instanceof UploadedFile) {
                $resourceFile = new ResourceFile();
                $resourceFile->setName($uploadedFile->getFilename());
                $resourceFile->setOriginalName($uploadedFile->getFilename());
                $resourceFile->setFile($uploadedFile);
                $em->persist($resourceFile);
                $resourceNode->setResourceFile($resourceFile);
            }
        }

        // Use by api platform
        $links = $resource->getResourceLinkArray();
        if ($links) {
            $groupRepo = $em->getRepository(CGroup::class);
            $courseRepo = $em->getRepository(Course::class);
            $sessionRepo = $em->getRepository(Session::class);

            foreach ($links as $link) {
                $resourceLink = new ResourceLink();
                if (isset($link['c_id']) && !empty($link['c_id'])) {
                    $course = $courseRepo->find($link['c_id']);
                    if (null !== $course) {
                        $resourceLink->setCourse($course);
                    } else {
                        throw new InvalidArgumentException(sprintf('Course #%s does not exists', $link['c_id']));
                    }
                }

                if (isset($link['sid']) && !empty($link['sid'])) {
                    $session = $sessionRepo->find($link['sid']);
                    if (null !== $session) {
                        $resourceLink->setSession($session);
                    } else {
                        throw new InvalidArgumentException(sprintf('Session #%s does not exists', $link['sid']));
                    }
                }

                if (isset($link['gid']) && !empty($link['gid'])) {
                    $group = $groupRepo->find($link['gid']);
                    if (null !== $group) {
                        $resourceLink->setGroup($group);
                    } else {
                        throw new InvalidArgumentException(sprintf('Group #%s does not exists', $link['gid']));
                    }
                }

                if (isset($link['visibility'])) {
                    $resourceLink->setVisibility((int) $link['visibility']);
                } else {
                    throw new InvalidArgumentException('Link needs a visibility key');
                }

                $resourceLink->setResourceNode($resourceNode);
                $em->persist($resourceLink);
            }
        }

        // Use by Chamilo.
        $this->setLinks($resourceNode, $resource, $em);

        if (null !== $resource->getParent()) {
            $resourceNode->setParent($resource->getParent()->getResourceNode());
        }

        error_log('Listener end, adding resource node');
        $resource->setResourceNode($resourceNode);

        // All resources should have a parent, except AccessUrl.
        if (!($resource instanceof AccessUrl) && null === $resourceNode->getParent()) {
            throw new InvalidArgumentException('Resource Node should have a parent');
        }
    }

    /**
     * When updating a Resource.
     */
    public function preUpdate(AbstractResource $resource, PreUpdateEventArgs $event): void
    {
        error_log('Resource listener preUpdate');
        $this->setLinks($resource->getResourceNode(), $resource, $event->getEntityManager());

        if ($resource->hasUploadFile()) {
            $uploadedFile = $resource->getUploadFile();

            // File upload
            if ($uploadedFile instanceof UploadedFile) {
                /*$resourceFile = new ResourceFile();
                $resourceFile->setName($uploadedFile->getFilename());
                $resourceFile->setOriginalName($uploadedFile->getFilename());
                $resourceFile->setFile($uploadedFile);
                $em->persist($resourceFile);*/
                //$resourceNode->setResourceFile($uploadedFile);
            }
        }
    }

    public function postUpdate(AbstractResource $resource, LifecycleEventArgs $event): void
    {
        //error_log('resource listener postUpdate');
        //$em = $event->getEntityManager();
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

    public function setLinks(ResourceNode $resourceNode, AbstractResource $resource, $em): void
    {
        error_log('Resource listener setLinks');
        $links = $resource->getResourceLinkEntityList();
        if ($links) {
            foreach ($links as $link) {
                error_log('Adding resource links');
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
                //error_log('link adding to node: '.$resource->getResourceNode()->getId());
                //error_log('link with user : '.$link->getUser()->getUsername());
                $resource->getResourceNode()->addResourceLink($link);

                $em->persist($link);
            }
        }
    }
}
