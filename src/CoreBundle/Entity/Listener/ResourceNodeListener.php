<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\ToolChain;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
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
    protected $resourceNodeRepository;

    /**
     * ResourceListener constructor.
     */
    public function __construct(
        SlugifyInterface $slugify,
        ToolChain $toolChain,
        RequestStack $request,
        Security $security,
        ResourceNodeRepository $resourceNodeRepository
    ) {
        $this->slugify = $slugify;
        $this->security = $security;
        $this->toolChain = $toolChain;
        $this->request = $request;
        $this->accessUrl = null;
        $this->resourceNodeRepository = $resourceNodeRepository;
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

        if ($resourceNode->hasResourceFile() && $resourceNode->hasEditableTextContent()) {
            $fileName = $this->resourceNodeRepository->getFilename($resourceNode->getResourceFile());
            error_log("fileName: $fileName");
            if ($fileName) {
                error_log('updated');
                $content = $resourceNode->getContent();
                $this->resourceNodeRepository->getFileSystem()->update($fileName, $content);
            }
        }

        return true;
    }

    public function postUpdate(ResourceNode $resourceNode, LifecycleEventArgs $event)
    {
        error_log('ResourceNode postUpdate');
    }
}
