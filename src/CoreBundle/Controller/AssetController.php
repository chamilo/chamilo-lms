<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Helpers\GlideAssetHelper;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/assets')]
class AssetController
{
    use ControllerTrait;

    #[Route(path: '/{category}/{path}', name: 'chamilo_core_asset_showfile', requirements: ['path' => '.+'], methods: ['GET'])]
    public function showFile(
        string $category,
        string $path,
        AssetRepository $assetRepository,
        GlideAssetHelper $glide,
        RequestStack $requestStack
    ): Response {
        $filePath = $category.'/'.$path;
        $exists = $assetRepository->getFileSystem()->fileExists($filePath);

        if ($exists) {
            $fileName = basename($filePath);
            $detector = new ExtensionMimeTypeDetector();
            $mimeType = (string) $detector->detectMimeTypeFromFile($filePath);
            // If image use glide
            if (str_contains($mimeType, 'image')) {
                $server = $glide->getServer();
                $request = $requestStack->getCurrentRequest();
                $params = $request->query->all();

                return $server->getImageResponse($filePath, $params);
            }

            // Only SCORM packages are meant to render markup here. Any other asset may come from a
            // user upload (e.g. a profile file field), so it is delivered as a neutral download.
            $forceDownload = Asset::SCORM !== $category
                && (str_contains($mimeType, 'html') || str_contains($mimeType, 'xml'));
            if ($forceDownload) {
                $mimeType = 'application/octet-stream';
            }

            $response = new StreamedResponse(
                function () use ($assetRepository, $filePath): void {
                    $outputStream = fopen('php://output', 'wb');
                    $stream = $assetRepository->getFileSystem()->readStream($filePath);

                    stream_copy_to_stream($stream, $outputStream);

                    fclose($stream);
                    fclose($outputStream);
                }
            );
            $disposition = $response->headers->makeDisposition(
                $forceDownload ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
                $fileName
            );
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-Type', $mimeType ?: 'application/octet-stream');

            if ($forceDownload) {
                $response->headers->set('X-Content-Type-Options', 'nosniff');
            }

            return $response;
        }

        throw new FileNotFoundException(\sprintf('File not found: %s', $path));
    }
}
