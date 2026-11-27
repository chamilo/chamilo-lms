<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\GlideAssetHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Security\ResourceAccessResolver;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CourseBundle\Repository\CLpRepository;
use League\Flysystem\PathTraversalDetected;
use League\Flysystem\WhitespacePathNormalizer;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        RequestStack $requestStack,
        Security $security,
        UserHelper $userHelper,
        CLpRepository $lpRepository,
        ResourceAccessResolver $resourceAccessResolver,
    ): Response {
        try {
            // Flysystem resolves ".." and "\" itself: decide on the path it will really read.
            $filePath = (new WhitespacePathNormalizer())->normalizePath($category.'/'.$path);
        } catch (PathTraversalDetected) {
            throw new FileNotFoundException(\sprintf('File not found: %s', $path));
        }
        $category = explode('/', $filePath, 2)[0];

        // A SCORM package is course content: only who can follow one of its learning paths reads it.
        if (Asset::SCORM === $category
            && !$security->isGranted('ROLE_ADMIN')
            && !$this->canReadScormPackage($filePath, $assetRepository, $lpRepository, $userHelper->getCurrent(), $resourceAccessResolver)
        ) {
            throw new AccessDeniedHttpException();
        }

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

    private function canReadScormPackage(
        string $filePath,
        AssetRepository $assetRepository,
        CLpRepository $lpRepository,
        ?User $user,
        ResourceAccessResolver $resourceAccessResolver,
    ): bool {
        // The package folder is named after its asset: scorm/<asset title>/...
        $folder = explode('/', $filePath, 3)[1] ?? '';
        if ('' === $folder) {
            return false;
        }

        $assets = $assetRepository->findBy(['category' => Asset::SCORM, 'title' => $folder]);
        foreach ($assets as $asset) {
            foreach ($lpRepository->findBy(['asset' => $asset]) as $lp) {
                $node = $lp->getResourceNode();
                if (null === $node) {
                    continue;
                }

                // Teachers and coaches also read the package of a draft learning path.
                if ($user instanceof User && $resourceAccessResolver->teachesResourceCourse($node, $user)) {
                    return true;
                }

                foreach ($node->getResourceLinks() as $link) {
                    if ($resourceAccessResolver->isOpenToVisitors($link)
                        || ($user instanceof User
                            && ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility()
                            && $resourceAccessResolver->mayReadPublishedLink($link, $user))
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
