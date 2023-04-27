<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Tool\ToolChain;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class ResourceNodeListener
{
    //protected $accessUrl;

    public function __construct(protected SlugifyInterface $slugify, protected ToolChain $toolChain, protected RequestStack $request, protected Security $security, protected ResourceNodeRepository $resourceNodeRepository)
    {
    }

    /*public function prePersist(ResourceNode $resourceNode, LifecycleEventArgs $event)
    {
        return true;
    }*/

    /**
     * When updating a Resource.
     * $resourceNode->getContent() was set in the BaseResourceFileAction (when calling the api).
     */
    public function preUpdate(ResourceNode $resourceNode, PreUpdateEventArgs $event)
    {
        if ($resourceNode->hasResourceFile() && $resourceNode->hasEditableTextContent()) {
            $fileName = $this->resourceNodeRepository->getFilename($resourceNode->getResourceFile());
            if ($fileName) {
                $content = $resourceNode->getContent();
                // Skip saving null.
                if (null !== $content) {
                    $this->resourceNodeRepository->getFileSystem()->write($fileName, $content);
                }
            }
        }

        return true;
    }

    /*public function postUpdate(ResourceNode $resourceNode, LifecycleEventArgs $event): void
    {
    }*/
}
