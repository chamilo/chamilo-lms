<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * Class ResourceListener.
 */
class ResourceListener
{
    protected $slugify;

    /**
     * ResourceListener constructor.
     *
     * @param SlugifyInterface $slugify
     */
    public function __construct(SlugifyInterface $slugify)
    {
        $this->slugify = $slugify;
    }

    /**
     * @param AbstractResource   $resource
     * @param LifecycleEventArgs $args
     */
    public function prePersist(AbstractResource $resource, LifecycleEventArgs $args)
    {
        //error_log('prePersist');
    }

    /**
     * When updating a Resource
     *
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(AbstractResource $resource, PreUpdateEventArgs $event)
    {
        /*error_log('preUpdate');
        $fieldIdentifier = $resource->getResourceFieldName();
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

    /**
     * @param AbstractResource   $resource
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(AbstractResource $resource, LifecycleEventArgs $args)
    {
        //error_log('postUpdate');
        $em = $args->getEntityManager();
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
