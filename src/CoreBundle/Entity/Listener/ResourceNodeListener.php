<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceToCourseInterface;
use Chamilo\CoreBundle\Entity\ResourceToRootInterface;
use Chamilo\CoreBundle\Entity\ResourceWithUrlInterface;
use Chamilo\CoreBundle\ToolChain;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

/**
 * Class ResourceNodeListener.
 */
class ResourceNodeListener
{
    protected $slugify;
    protected $request;
    protected $accessUrl;

    /**
     * ResourceListener constructor.
     */
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

    public function prePersist(ResourceNode $resourceNode, LifecycleEventArgs $event)
    {
        error_log('resource node prePersist');

        return true;

    }

    /**
     * When updating a Resource.
     */
    public function preUpdate(ResourceNode $resourceNode, PreUpdateEventArgs $event)
    {
        error_log('resource node preUpdate');
        return true;
    }

    public function postUpdate(ResourceNode $resourceNode, LifecycleEventArgs $event)
    {
        error_log('ResourceNode postUpdate');
    }

}
