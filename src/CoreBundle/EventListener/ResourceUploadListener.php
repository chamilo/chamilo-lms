<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Doctrine\Common\Persistence\ObjectManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Uploader\File\FlysystemFile;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;

/**
 * Class ResourceUploadListener.
 */
class ResourceUploadListener
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * ResourceUploadListener constructor.
     *
     * @param ObjectManager $om
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * @return ResponseInterface
     */
    public function onUpload(PostPersistEvent $event)
    {
        /** @var AbstractResource $file */
        $file = $event->getFile();
        $json = [];

        $json['name'] = $file->getResourceName();

        $json['url'] = '#';
        $json['size'] = format_file_size($file->getSize());
        $json['type'] = '';
        $json['result'] = 'ok';
        error_log('ResourceUploadListener:onUpload listener'.$file->getPath());

        // If everything went fine
        $response = $event->getResponse();
        $list[] = $json;
        $response['files'] = $list;

        return $response;
    }
}
