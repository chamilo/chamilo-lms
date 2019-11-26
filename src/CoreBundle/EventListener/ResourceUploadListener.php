<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Doctrine\Common\Persistence\ObjectManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class ResourceUploadListener.
 */
class ResourceUploadListener
{
    /**
     * @var ObjectManager
     */
    private $om;
    private $router;

    /**
     * ResourceUploadListener constructor.
     */
    public function __construct(ObjectManager $om, RouterInterface $router)
    {
        $this->om = $om;
        $this->router = $router;
    }

    /**
     * @return ResponseInterface
     */
    public function onUpload(PostPersistEvent $event)
    {
        /** @var AbstractResource $resource */
        $resource = $event->getFile();
        $resourceNode = $resource->getResourceNode();

        $tool = $resourceNode->getResourceType()->getTool();
        $type = $resourceNode->getResourceType()->getName();

        $output = [[
            'name' => $resource->getResourceName(),
            //'thumbnail_url' => '',
            'url' => $this->router->generate(
                'chamilo_core_resource_view',
                ['tool' => $tool, 'type' => $type, 'id' => $resourceNode->getId()]
            ),
            'size' => format_file_size($resource->getSize()),
            'type' => '',
            'result' => 'ok',
        ]];

        // If everything went fine
        $response = $event->getResponse();
        $response['files'] = $output;

        return $response;
    }
}
