<?php

/* For license terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\ExerciseMonitoring\Controller;

use Chamilo\CoreBundle\Framework\Container;
use ExerciseMonitoringPlugin;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class SnapshotController
{
    public function __construct(
        private ExerciseMonitoringPlugin $plugin,
        private HttpRequest $request,
    ) {}

    public function __invoke(): HttpResponse
    {
        if (!$this->plugin->isEnabled(true)) {
            throw new AccessDeniedHttpException(HttpResponse::$statusTexts[HttpResponse::HTTP_FORBIDDEN]);
        }

        $filename = $this->request->query->getString('f');

        if (empty($filename)) {
            throw new NotFoundHttpException(HttpResponse::$statusTexts[HttpResponse::HTTP_NOT_FOUND]);
        }

        // Prevent path traversal attacks
        $normalizedPath = str_replace(['../', '..\\', "\0"], '', $filename);

        if ($normalizedPath !== $filename) {
            throw new AccessDeniedHttpException(HttpResponse::$statusTexts[HttpResponse::HTTP_FORBIDDEN]);
        }

        /** @var FilesystemOperator $pluginsFilesystem */
        $pluginsFilesystem = Container::$container->get('oneup_flysystem.plugins_filesystem');

        if (!$pluginsFilesystem->fileExists($normalizedPath)) {
            throw new NotFoundHttpException(HttpResponse::$statusTexts[HttpResponse::HTTP_NOT_FOUND]);
        }

        $content = $pluginsFilesystem->read($normalizedPath);
        $mimeType = $pluginsFilesystem->mimeType($normalizedPath);

        return new HttpResponse($content, HttpResponse::HTTP_OK, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
