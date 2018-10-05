<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ResourceController.
 *
 * @author Julio Montoya <gugli100@gmail.com>.
 *
 * @Route("/resource")
 *
 * @package Chamilo\CoreBundle\Controller
 */
class ResourceDownloadController extends BaseController
{
    /**
     * Upload form.
     *
     * @Route("/upload/{type}/{id}", name="resource_upload", methods={"GET", "POST"}, options={"expose"=true})
     *
     * @return Response
     */
    public function showUploadFormAction($type, $id): Response
    {
        //$helper = $this->container->get('oneup_uploader.templating.uploader_helper');
        //$endpoint = $helper->endpoint('courses');
        return $this->render(
            '@ChamiloTheme/Resource/upload.html.twig',
            [
                'identifier' => $id,
                'type' => $type,
            ]
        );
    }

    /**
     * Downloads the file courses/MATHS/document/file.jpg to the user.
     *
     * @Route("/download/{course}/", name="resource_download", methods={"GET"}, options={"expose"=true})
     *
     * @todo check permissions
     *
     * @param string $course
     *
     * @return \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function downloadFileAction(Request $request, $course)
    {
        try {
            /** @var Filesystem $fs */
            $fs = $this->container->get('oneup_flysystem.courses_filesystem');
            $file = $request->get('file');

            $path = $course.'/document/'.$file;

            // Has folder
            if (!$fs->has($course)) {
                return $this->abort();
            }

            // Has file
            if (!$fs->has($path)) {
                return $this->abort();
            }

            /** @var Local $adapter */
            $adapter = $fs->getAdapter();
            $filePath = $adapter->getPathPrefix().$path;

            $response = new BinaryFileResponse($filePath);

            // To generate a file download, you need the mimetype of the file
            $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

            // Set the mimetype with the guesser or manually
            if ($mimeTypeGuesser->isSupported()) {
                // Guess the mimetype of the file according to the extension of the file
                $response->headers->set('Content-Type', $mimeTypeGuesser->guess($filePath));
            } else {
                // Set the mimetype of the file manually, in this case for a text file is text/plain
                $response->headers->set('Content-Type', 'text/plain');
            }

            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                basename($filePath)
            );

            return $response;
        } catch (\InvalidArgumentException $e) {
            return $this->abort();
        }
    }

    /**
     * Gets a document in browser courses/MATHS/document/file.jpg to the user.
     *
     * @Route("/get/{course}/", name="resource_get", methods={"GET"}, options={"expose"=true})
     *
     * @todo check permissions
     *
     * @param string $course
     *
     * @return \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getFileAction(Request $request, $course)
    {
        try {
            /** @var Filesystem $fs */
            $fs = $this->container->get('oneup_flysystem.courses_filesystem');
            $file = $request->get('file');

            $path = $course.'/document/'.$file;

            // Has folder
            if (!$fs->has($course)) {
                return $this->abort();
            }

            // Has file
            if (!$fs->has($path)) {
                return $this->abort();
            }

            /** @var Local $adapter */
            $adapter = $fs->getAdapter();
            $filePath = $adapter->getPathPrefix().$path;

            return $this->file($filePath, null, ResponseHeaderBag::DISPOSITION_INLINE);
        } catch (\InvalidArgumentException $e) {
            return $this->abort();
        }
    }
}
