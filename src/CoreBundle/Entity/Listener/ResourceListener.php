<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

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
        error_log('prePersist');
    }

    /**
     * @param AbstractResource   $resource
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(AbstractResource $resource, LifecycleEventArgs $args)
    {
        error_log('preUpdate');
    }

    /**
     * @param AbstractResource   $resource
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(AbstractResource $resource, LifecycleEventArgs $args)
    {
        error_log('postUpdate');

        $em = $args->getEntityManager();

        // Updates resource node name with the resource name.
        $resourceNode = $resource->getResourceNode();
        $name = $resource->getResourceName();

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
        $em->flush();
    }
}
