<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\ResourceInterface;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;
use Symfony\Component\Routing\RouterInterface;

class ResourceUploadListener
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @return ResponseInterface
     */
    public function onUpload(PostPersistEvent $event)
    {
        /** @var ResourceInterface $resource */
        $resource = $event->getFile();
        $courseId = $event->getRequest()->get('cid');
        $sessionId = $event->getRequest()->get('sid');

        $resourceNode = $resource->getResourceNode();

        $tool = $resourceNode->getResourceType()->getTool();
        $type = $resourceNode->getResourceType()->getName();

        $output = [[
            'name' => $resource->getResourceName(),
            //'thumbnail_url' => '',
            'url' => $this->router->generate(
                'chamilo_core_resource_view',
                [
                    'id' => $resourceNode->getId(),
                    'tool' => $tool,
                    'type' => $type,
                    'cid' => $courseId,
                    'sid' => $sessionId,
                ]
            ),
            'size' => format_file_size($resource->getResourceNode()->getResourceFile()->getSize()),
            'type' => '',
            'result' => 'ok',
        ]];

        // If everything went fine
        $response = $event->getResponse();
        $response['files'] = $output;

        return $response;
    }
}
