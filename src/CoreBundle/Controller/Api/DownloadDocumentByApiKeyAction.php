<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DownloadDocumentByApiKeyAction extends AbstractController
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly ResourceNodeRepository $resourceNodeRepository
    ) {}

    public function __invoke(CDocument $data, Request $request): StreamedResponse
    {
        if (!$this->isSettingEnabled($this->settingsManager->getSetting('webservice.allow_download_documents_by_api_key', true))) {
            throw new AccessDeniedHttpException('Document download by API key is disabled.');
        }

        if (true !== $request->attributes->get('_chamilo_webservice_api_key')) {
            throw new AccessDeniedHttpException('This endpoint requires API key authentication.');
        }

        if ('file' !== $data->getFiletype()) {
            throw new NotFoundHttpException('Document file not found.');
        }

        $resourceNode = $data->getResourceNode();

        if (!$resourceNode instanceof ResourceNode) {
            throw new NotFoundHttpException('Document resource node not found.');
        }

        if (!$this->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to download this document.');
        }

        $resourceFile = $resourceNode->getResourceFiles()->first();

        if (!$resourceFile instanceof ResourceFile) {
            throw new NotFoundHttpException('Document resource file not found.');
        }

        $stream = $this->resourceNodeRepository->getResourceNodeFileStream($resourceNode, $resourceFile);

        if (!is_resource($stream)) {
            throw new NotFoundHttpException('Document file stream not found.');
        }

        $fileName = $resourceFile->getOriginalName() ?: $data->getTitle();
        $mimeType = $resourceFile->getMimeType() ?: 'application/octet-stream';

        $response = new StreamedResponse(static function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        });

        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName
            )
        );

        if ($resourceFile->getSize() > 0) {
            $response->headers->set('Content-Length', (string) $resourceFile->getSize());
        }

        return $response;
    }

    private function isSettingEnabled(mixed $value): bool
    {
        if (true === $value || 1 === $value) {
            return true;
        }

        $normalized = strtolower(trim((string) $value));

        return 'true' === $normalized || '1' === $normalized;
    }
}
