<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\ServiceHelper\ThemeHelper;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/themes')]
class ThemeController extends AbstractController
{
    public function __construct(
        private readonly ThemeHelper $themeHelper
    ) {}

    /**
     * @throws FilesystemException
     */
    #[Route('/{name}/{path}', name: 'theme_asset', requirements: ['path' => '.+'])]
    public function index(
        string $name,
        string $path,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        FilesystemOperator $filesystem
    ): Response {
        $themeDir = basename($name);

        if (!$filesystem->directoryExists($themeDir)) {
            throw $this->createNotFoundException('The folder name does not exist.');
        }

        $filePath = $this->themeHelper->getFileLocation($path);

        if (!$filePath) {
            throw $this->createNotFoundException('The requested file does not exist.');
        }

        $response = new StreamedResponse(function () use ($filesystem, $filePath): void {
            $outputStream = fopen('php://output', 'wb');

            $fileStream = $filesystem->readStream($filePath);

            stream_copy_to_stream($fileStream, $outputStream);

            fclose($outputStream);
            fclose($fileStream);
        });

        $mimeType = $filesystem->mimeType($filePath);

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($path));

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $mimeType ?: 'application/octet-stream');

        return $response;
    }
}
