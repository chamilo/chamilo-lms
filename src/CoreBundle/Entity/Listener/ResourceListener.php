<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Component\Naming\SmartUniqueNamer;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use League\Flysystem\MountManager;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Naming\HashNamer;
use Vich\UploaderBundle\Util\FilenameUtils;

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
    public function postUpdate(AbstractResource $resource, LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        // Updates resource node name with the resource name.
        $node = $resource->getResourceNode();
        $name = $resource->getResourceName();

        if ($node->hasResourceFile()) {
            $originalExtension = pathinfo($name, PATHINFO_EXTENSION);
            $originalBasename = \basename($name, '.'.$originalExtension);

            $modified = sprintf('%s.%s', $this->slugify->slugify($originalBasename), $originalExtension);
        } else {
            $modified = $this->slugify->slugify($name);
        }
        $node->setName($modified);

        $em->persist($node);
        $em->flush();
    }
}
