<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Oneup\UploaderBundle\Controller\BlueimpController;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\File\FilesystemFile;
use Oneup\UploaderBundle\Uploader\Response\EmptyResponse;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ResourceUploaderController.
 */
class ResourceUploadController extends BlueimpController
{
    /**
     * This will upload an image to the selected node id.
     * This action is listen by the ResourceUploadListener.
     */
    public function upload(): JsonResponse
    {
        error_log('upload');
        $container = $this->container;
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        $request = $this->getRequest();

        $type = $request->get('type');
        $tool = $request->get('tool');
        $id = $request->get('id');
        $courseId = $request->get('cid');
        $sessionId = $request->get('sid');

        $course = null;
        if (!empty($courseId)) {
            $course = $doctrine->getRepository('ChamiloCoreBundle:Course')->find($courseId);
        }

        $session = null;
        if (!empty($sessionId)) {
            $session = $doctrine->getRepository('ChamiloCoreBundle:Session')->find($sessionId);
        }

        $token = $container->get('security.token_storage')->getToken();
        $user = $token->getUser();

        // Create repository from tool and type.
        $factory = $container->get('Chamilo\CoreBundle\Repository\ResourceFactory');
        $repoService = $factory->getRepositoryService($tool, $type);

        /** @var ResourceRepository $repo */
        $repo = $container->get($repoService);

        /** @var ResourceNode $parent */
        $parent = $repo->getResourceNodeRepository()->find($id);

        /*$checker = $container->get('security.authorization_checker');
        if (!$checker->isGranted($parent, ResourceNodeVoter::CREATE)) {
            return new AccessDeniedException('No permissions');
        }*/

        //$chunked = null !== $request->headers->get('content-range');
        $response = new EmptyResponse();
        $files = $this->getFiles($request->files);

        try {
            /** @var UploadedFile $file */
            foreach ($files as $file) {
                try {
                    if (!($file instanceof FileInterface)) {
                        $file = new FilesystemFile($file);
                    }

                    $this->validate($file, $request, $response);
                    $this->dispatchPreUploadEvent($file, $response, $request);

                    $resource = $repo->saveUpload($file, $course, $session);

                    // @todo fix correct $parent
                    $resource->setParent($parent);
                    $resource->addCourseLink(
                        $course,
                        $session
                    );
                    $em->persist($resource);
                    $em->flush();

                    $repo->addFile($resource, $file);
                    $em->flush();
                    // Finish uploading.

                    $this->dispatchPostEvents($resource, $response, $request);
                    /*$chunked ?
                        $this->handleChunkedUpload($file, $response, $request) :
                        $this->handleUpload($file, $response, $request);*/
                } catch (UploadException $e) {
                    $this->errorHandler->addException($response, $e);
                }
            }
        } catch (UploadException $e) {
            return new JsonResponse([]);
        }

        return $this->createSupportedJsonResponse($response->assemble());
    }
}
