<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\ToolChain;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

/**
 * Class ResourceListener.
 */
class ResourceListener
{
    protected $slugify;
    protected $request;

    /**
     * ResourceListener constructor.
     */
    public function __construct(SlugifyInterface $slugify, ToolChain $toolChain, RequestStack $request, Security $security)
    {
        $this->slugify = $slugify;
        $this->security = $security;
        $this->toolChain = $toolChain;
        $this->request = $request;
    }

    public function prePersist(AbstractResource $resource, LifecycleEventArgs $args)
    {
        // Add resource node
        $em = $args->getEntityManager();
        $id = $this->request->getCurrentRequest()->getSession()->get('access_url_id');
        $url = $em->getRepository('ChamiloCoreBundle:AccessUrl')->find($id);
        $creator = $this->security->getUser();

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

        $repo = $em->getRepository('ChamiloCoreBundle:Resource\ResourceType');
        $class = str_replace('Entity', 'Repository', get_class($args->getEntity()));
        $class .= 'Repository';
        $name = $this->toolChain->getResourceTypeNameFromRepository($class);
        $resourceType = $repo->findOneBy(['name' => $name]);
        $resourceNode
            ->setTitle($resourceName)
            ->setSlug($slug)
            ->setCreator($creator)
            ->setResourceType($resourceType)
        ;

        $resourceNode->setParent($url->getResourceNode());
        $resource->setResourceNode($resourceNode);
        $em->persist($resourceNode);

        return $resourceNode;
    }

    /**
     * When updating a Resource.
     */
    public function preUpdate(AbstractResource $resource, PreUpdateEventArgs $event)
    {
        /*error_log('preUpdate');
        error_log($fieldIdentifier);
        $em = $event->getEntityManager();
        if ($event->hasChangedField($fieldIdentifier)) {
            error_log('changed');
            $oldValue = $event->getOldValue($fieldIdentifier);
            error_log($oldValue);
            $newValue = $event->getNewValue($fieldIdentifier);
            error_log($newValue);
            //$this->updateResourceName($resource, $newValue, $em);
        }*/
    }

    public function postUpdate(AbstractResource $resource, LifecycleEventArgs $args)
    {
        //error_log('postUpdate');
        //$em = $args->getEntityManager();
        //$this->updateResourceName($resource, $resource->getResourceName(), $em);
    }

    public function updateResourceName(AbstractResource $resource, $newValue, $em)
    {
        // Updates resource node name with the resource name.
        /*$resourceNode = $resource->getResourceNode();

        $newName = $resource->getResourceName();

        $name = $resourceNode->getSlug();

        if ($resourceNode->hasResourceFile()) {
            $originalExtension = pathinfo($name, PATHINFO_EXTENSION);
            $originalBasename = \basename($name, $originalExtension);
            $modified = sprintf('%s.%s', $this->slugify->slugify($originalBasename), $originalExtension);
        } else {
            $modified = $this->slugify->slugify($name);
        }

        error_log($name);
        error_log($modified);

        $resourceNode->setSlug($modified);

        if ($resourceNode->hasResourceFile()) {
            $resourceNode->getResourceFile()->setOriginalName($name);
        }
        $em->persist($resourceNode);
        $em->flush();*/
    }
}
