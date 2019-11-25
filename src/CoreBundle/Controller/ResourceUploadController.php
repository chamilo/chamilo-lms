<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Resource;
use Chamilo\CourseBundle\Entity\CDocument;
use Oneup\UploaderBundle\Uploader\ErrorHandler\ErrorHandlerInterface;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\File\FilesystemFile;
use Oneup\UploaderBundle\Uploader\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Oneup\UploaderBundle\Controller\BlueimpController;
use Oneup\UploaderBundle\Uploader\Response\EmptyResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class ResourceUploaderController.
 */
class ResourceUploadController extends BlueimpController
{
    /**
     * @return JsonResponse
     */
    public function upload()
    {
        error_log('upload');

        $container = $this->container;
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        $request = $this->getRequest();

        $type = $request->get('type');
        $tool = $request->get('tool');
        $id = $request->get('id');
        $courseCode = $request->get('cidReq');
        $sessionId = $request->get('id_session');

        $controller = $container->get('Chamilo\CoreBundle\Controller\ResourceController');

        $course = null;
        if (!empty($courseCode)) {
            $course = $doctrine->getRepository('ChamiloCoreBundle:Course')->findOneBy(['code' => $courseCode]);
        }

        $session = null;
        if (!empty($sessionId)) {
            $session = $doctrine->getRepository('ChamiloCoreBundle:Session')->find($sessionId);
        }

        $token = $container->get('security.token_storage')->getToken();
        $user = $token->getUser();

        $repo = $controller->getRepository($tool, $type);
        /** @var ResourceNode $parent */
        $parent = $repo->getResourceNodeRepository()->find($id);

        /*$checker = $container->get('security.authorization_checker');
        if (!$checker->isGranted($parent, ResourceNodeVoter::CREATE)) {
            return new AccessDeniedException('No permissions');
        }*/

        $chunked = null !== $request->headers->get('content-range');

        $response = new EmptyResponse();
        $files = $this->getFiles($request->files);
        try {
            /** @var UploadedFile $file */
            foreach ($files as $file) {
                try {
                    $title = $file->getClientOriginalName();

                    if (!($file instanceof FileInterface)) {
                        $file = new FilesystemFile($file);
                    }

                    $this->validate($file, $request, $response);

                    $this->dispatchPreUploadEvent($file, $response, $request);

                    $document = new CDocument();
                    $document
                        ->setFiletype('file')
                        ->setTitle($title)
                        ->setSize($file->getSize())
                        ->setCourse($course)
                    ;

                    $em->persist($document);
                    $resourceNode = $repo->createNodeForResource($document, $user, $parent, $file);

                    $repo->addResourceNodeToCourse(
                        $resourceNode,
                        ResourceLink::VISIBILITY_PUBLISHED,
                        $course,
                        $session,
                        null
                    );

                    $em->flush();

                    $this->dispatchPostEvents($document, $response, $request);

                    /*$chunked ?
                        $this->handleChunkedUpload($file, $response, $request) :
                        $this->handleUpload($file, $response, $request);*/
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
