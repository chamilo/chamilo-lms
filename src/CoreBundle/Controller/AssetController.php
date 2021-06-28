<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Utils\GlideAsset;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/assets')]
class AssetController
{
    use ControllerTrait;

    /**
     * @Route("/{category}/{path}", methods={"GET"}, requirements={"path"=".+"}, name="chamilo_core_asset_showfile")
     */
    public function showFile(
        $category,
        $path,
        AssetRepository $assetRepository,
        GlideAsset $glide,
        RequestStack $requestStack
    ) {
        $filePath = $category.'/'.$path;
        $exists = $assetRepository->getFileSystem()->fileExists($filePath);

        if ($exists) {
            $fileName = basename($filePath);
            $detector = new ExtensionMimeTypeDetector();
            $mimeType = $detector->detectMimeTypeFromFile($filePath);
            // If image use glide, because why not.
            if (str_contains($mimeType, 'image')) {
                $server = $glide->getServer();
                $request = $requestStack->getCurrentRequest();
                $params = $request->query->all();

                // The filter overwrites the params from GET.
                /*if (!empty($filter)) {
                    $params = $glide->getFilters()[$filter] ?? [];
                }*/

                // The image was cropped manually by the user, so we force to render this version,
                // no matter other crop parameters.
                //$crop = $resourceFile->getCrop();
                /*if (!empty($crop)) {
                    $params['crop'] = $crop;
                }*/

                return $server->getImageResponse($filePath, $params);
            }

            $stream = $assetRepository->getFileSystem()->readStream($filePath);

            $response = new StreamedResponse(
                function () use ($stream): void {
                    stream_copy_to_stream($stream, fopen('php://output', 'wb'));
                }
            );
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $fileName
            );
            $response->headers->set('Content-Disposition', $disposition);

            //$response->headers->set('Content-Type', $mimeType ?: 'application/octet-stream');

            return $response;
        }

        throw new FileNotFoundException($path);
    }
}
