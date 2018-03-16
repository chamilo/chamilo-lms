<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class ResourceController
 * author Julio Montoya <gugli100@gmail.com>.
 *
 * @package Chamilo\CoreBundle\Controller
 */
class ResourceController extends BaseController
{
    /**
     * Gets a document from the courses/MATHS/document/file.jpg to the user.
     *
     * @todo check permissions
     *
     * @param string $course
     * @param string $file
     *
     * @return \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getDocumentAction($course, $file)
    {
        try {
            /** @var Filesystem $fs */
            $fs = $this->container->get('oneup_flysystem.courses_filesystem');
            $path = $course.'/document/'.$file;

            // Has folder
            if (!$fs->has($course)) {
                return $this->abort();
            }

            /** @var \League\Flysystem\Adapter\Local $adapter */
            $adapter = $fs->getAdapter();
            $filePath = $adapter->getPathPrefix().$path;

            return new BinaryFileResponse($filePath);
        } catch (\InvalidArgumentException $e) {
            return $this->abort();
        }
    }

    /**
     * Gets a document from the data/courses/MATHS/document/file.jpg to the user.
     *
     * @todo check permissions
     *
     * @param Application $app
     * @param string      $courseCode
     * @param string      $file
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getCourseUploadFileAction(
        $app,
        $courseCode,
        $file
    ) {
        try {
            $file = $app['chamilo.filesystem']->getCourseUploadFile(
                $courseCode,
                $file
            );

            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }

    /**
     * Gets a document from the data/courses/MATHS/scorm/file.jpg to the user.
     *
     * @todo check permissions
     *
     * @param Application $app
     * @param string      $courseCode
     * @param string      $file
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getScormDocumentAction($app, $courseCode, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->getCourseScormDocument(
                $courseCode,
                $file
            );

            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }

    /**
     * Gets a document from the data/default_platform_document/* folder.
     *
     * @param Application $app
     * @param string      $file
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getDefaultCourseDocumentAction($app, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->get(
                'default_course_document/'.$file
            );

            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }

    /**
     * @param Application $app
     * @param $groupId
     * @param $file
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getGroupFile($app, $groupId, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->get(
                'upload/groups/'.$groupId.'/'.$file
            );

            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }

    /**
     * @param Application $app
     * @param $file
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getUserFile($app, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->get('upload/users/'.$file);

            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }
}
