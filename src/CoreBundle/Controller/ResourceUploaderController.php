<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Oneup\UploaderBundle\Controller\BlueimpController;
use Oneup\UploaderBundle\Uploader\Response\EmptyResponse;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ResourceUploaderController.
 *
 * @package Chamilo\CoreBundle\Controller
 */
class ResourceUploaderController extends BlueimpController
{
    /**
     * @return JsonResponse
     */
    public function upload()
    {
        error_log('upload!!!');
        $request = $this->getRequest();
        $response = new EmptyResponse();
        $files = $this->getFiles($request->files);

        $chunked = null !== $request->headers->get('content-range');

        try {
            /** @var UploadedFile $file */
            foreach ($files as $file) {
                try {
                    $file->getFilename();
                    $type = $request->get('type');

                    if ($type === 'course') {
                        $courseCode = $request->get('identifier');
                        $this->container->get('');
                    }

                    $chunked ?
                        $this->handleChunkedUpload($file, $response, $request) :
                        $this->handleUpload($file, $response, $request);
                } catch (UploadException $e) {
                    $this->errorHandler->addException($response, $e);
                }
            }
        } catch (UploadException $e) {
            // return nothing
            return new JsonResponse([]);
        }

        return $this->createSupportedJsonResponse($response->assemble());
    }
}
