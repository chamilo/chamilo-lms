<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResolveResourceFileContentUrlSubscriber implements EventSubscriberInterface
{
    private UrlGeneratorInterface $generator;
    private ResourceNodeRepository $resourceNodeRepository;

    public function __construct(UrlGeneratorInterface $generator, ResourceNodeRepository $resourceNodeRepository)
    {
        $this->generator = $generator;
        $this->resourceNodeRepository = $resourceNodeRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            //    KernelEvents::VIEW => ['onPreSerialize', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function onPreSerialize(ViewEvent $event): void
    {
        return;
        /*$controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if ($controllerResult instanceof Response || !$request->attributes->getBoolean('_api_respond', true)) {
            return;
        }

        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if (!($attributes = RequestAttributesExtractor::extractAttributes($request)) ||
            //!\is_a($attributes['resource_class'], ResourceFile::class, true)
            !is_a($attributes['resource_class'], AbstractResource::class, true)
        ) {
            return;
        }
        $mediaObjects = $controllerResult;

        if (!is_iterable($mediaObjects)) {
            $mediaObjects = [$mediaObjects];
        }

        $getFile = $request->get('getFile');

        $courseId = (int) $request->get('cid');
        if (empty($courseId)) {
            // Try with cid from session
            $courseId = (int) $request->getSession()->get('cid');
        }

        $sessionId = (int) $request->get('sid');
        if (empty($sessionId)) {
            $sessionId = (int) $request->getSession()->get('sid');
        }

        $groupId = (int) $request->get('gid');
        if (empty($groupId)) {
            $groupId = (int) $request->getSession()->get('gid');
        }

        foreach ($mediaObjects as $mediaObject) {
            if (!$mediaObject instanceof AbstractResource) {
                continue;
            }
            if ($mediaObject->hasResourceNode()) {
                $resourceNode = $mediaObject->getResourceNode();

                $params = [
                    'id' => $resourceNode->getId(),
                    'cid' => $courseId,
                    'sid' => $sessionId,
                    'gid' => $groupId,
                    'tool' => $resourceNode->getResourceType()->getTool()->getName(),
                    'type' => $resourceNode->getResourceType()->getName(),
                ];

                //if ($getFile) {
                // Get all links from resource.
                $mediaObject->setResourceLinkListFromEntity();
                //}

                $mediaObject->contentUrl = $this->generator->generate('chamilo_core_resource_view', $params);
                $mediaObject->downloadUrl = $this->generator->generate('chamilo_core_resource_download', $params);

                if ($getFile &&
                    $resourceNode->hasResourceFile() &&
                    $resourceNode->hasEditableTextContent()
                ) {
                    $mediaObject->contentFile = $this->resourceNodeRepository->getResourceNodeFileContent(
                        $resourceNode
                    );
                }
            }
        }*/
    }
}
