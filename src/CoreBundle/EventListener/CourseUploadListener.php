<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Uploader\File\FlysystemFile;

/**
 * Class UploadListener.
 *
 * @package Chamilo\CoreBundle\EventListener
 */
class CourseUploadListener
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * CourseUploadListener constructor.
     *
     * @param ObjectManager $om
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * @param PostPersistEvent $event
     *
     * @return \Oneup\UploaderBundle\Uploader\Response\ResponseInterface
     */
    public function onUpload(PostPersistEvent $event)
    {
        /** @var FlysystemFile $file */
        $file = $event->getFile();

        error_log('CourseUploadListener:onUpload listener'.$file->getPathname());

        // If everything went fine
        $response = $event->getResponse();
        $response['success'] = true;

        return $response;
    }
}
