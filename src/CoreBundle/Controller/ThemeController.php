<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Helpers\ThemeHelper;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const DIRECTORY_SEPARATOR;

#[Route('/themes')]
class ThemeController extends AbstractController
{
    public function __construct(
        private readonly ThemeHelper $themeHelper
    ) {}

    /**
     * Upload logos (SVG/PNG) for theme header/email.
     */
    #[Route(
        '/{slug}/logos',
        name: 'theme_logos_upload',
        methods: ['POST'],
        priority: 10
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function uploadLogos(
        string $slug,
        Request $request,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        FilesystemOperator $fs
    ): JsonResponse {
        $map = [
            'header_svg' => 'images/header-logo.svg',
            'header_png' => 'images/header-logo.png',
            'email_svg' => 'images/email-logo.svg',
            'email_png' => 'images/email-logo.png',
        ];

        if (!$fs->directoryExists($slug)) {
            $fs->createDirectory($slug);
        }
        if (!$fs->directoryExists("$slug/images")) {
            $fs->createDirectory("$slug/images");
        }

        $results = [];

        foreach ($map as $field => $relativePath) {
            $file = $request->files->get($field);
            if (!$file) {
                $results[$field] = 'skipped';

                continue;
            }

            $ext = strtolower((string) $file->getClientOriginalExtension());
            $mime = (string) $file->getMimeType();

            // SVG
            if (str_ends_with($field, '_svg')) {
                if ('image/svg+xml' !== $mime && 'svg' !== $ext) {
                    $results[$field] = 'invalid_mime';

                    continue;
                }
                $content = @file_get_contents($file->getPathname()) ?: '';
                $content = $this->sanitizeSvg($content);
                $this->ensureDir($fs, $slug.'/images');
                $fs->write($slug.'/'.$relativePath, $content);
                $results[$field] = 'uploaded';

                continue;
            }

            // PNG
            if ('image/png' !== $mime && 'png' !== $ext) {
                $results[$field] = 'invalid_mime';

                continue;
            }
            $info = @getimagesize($file->getPathname());
            if (!$info) {
                $results[$field] = 'invalid_image';

                continue;
            }
            [$w, $h] = $info;

            if ('header_png' === $field && ($w > 190 || $h > 60)) {
                $results[$field] = 'invalid_dimensions_header_png';

                continue;
            }

            $this->ensureDir($fs, $slug.'/images');
            $stream = fopen($file->getPathname(), 'rb');
            $fs->writeStream($slug.'/'.$relativePath, $stream);
            if (\is_resource($stream)) {
                fclose($stream);
            }

            $results[$field] = 'uploaded';
        }

        return $this->json(['status' => 'ok', 'results' => $results], Response::HTTP_CREATED);
    }

    /**
     * Delete a specific logo.
     */
    #[Route(
        '/{slug}/logos/{type}',
        name: 'theme_logos_delete',
        requirements: ['type' => 'header_svg|header_png|email_svg|email_png'],
        methods: ['DELETE'],
        priority: 10
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteLogo(
        string $slug,
        string $type,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        FilesystemOperator $fs
    ): JsonResponse {
        $map = [
            'header_svg' => 'images/header-logo.svg',
            'header_png' => 'images/header-logo.png',
            'email_svg' => 'images/email-logo.svg',
            'email_png' => 'images/email-logo.png',
        ];

        $path = $slug.'/'.$map[$type];
        if ($fs->fileExists($path)) {
            $fs->delete($path);
        }

        return $this->json(['status' => 'deleted']);
    }

    /**
     * Serve an asset from the theme.
     * - If ?strict=1 → only serves {name}/{path}. If it doesn't exist, 404 (no fallback).
     * - If strict isn't available → tries {name}/{path} and then the default theme.
     */
    #[Route(
        '/{name}/{path}',
        name: 'theme_asset',
        requirements: ['path' => '.+'],
        methods: ['GET'],
        priority: -10
    )]
    public function index(
        string $name,
        string $path,
        Request $request,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        FilesystemOperator $filesystem
    ): Response {
        $themeDir = basename($name);
        $strict = $request->query->getBoolean('strict', false);

        if (!$filesystem->directoryExists($themeDir)) {
            throw $this->createNotFoundException('The folder name does not exist.');
        }

        $filePath = null;

        if ($strict) {
            $candidate = $themeDir.DIRECTORY_SEPARATOR.$path;
            if ($filesystem->fileExists($candidate)) {
                $filePath = $candidate;
            } else {
                throw $this->createNotFoundException('The requested file does not exist.');
            }
        } else {
            $candidates = [
                $themeDir.DIRECTORY_SEPARATOR.$path,
                ThemeHelper::DEFAULT_THEME.DIRECTORY_SEPARATOR.$path,
            ];
            foreach ($candidates as $c) {
                if ($filesystem->fileExists($c)) {
                    $filePath = $c;

                    break;
                }
            }
            if (!$filePath) {
                throw $this->createNotFoundException('The requested file does not exist.');
            }
        }

        $response = new StreamedResponse(function () use ($filesystem, $filePath): void {
            $out = fopen('php://output', 'wb');
            $in = $filesystem->readStream($filePath);
            stream_copy_to_stream($in, $out);
            fclose($out);
            fclose($in);
        });

        $mimeType = $filesystem->mimeType($filePath) ?: 'application/octet-stream';
        if (str_ends_with(strtolower($filePath), '.svg')) {
            $mimeType = 'image/svg+xml';
        }

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            basename($path)
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    private function ensureDir(FilesystemOperator $fs, string $dir): void
    {
        if (!$fs->directoryExists($dir)) {
            $fs->createDirectory($dir);
        }
    }

    private function sanitizeSvg(string $svg): string
    {
        $svg = preg_replace('#<script[^>]*>.*?</script>#is', '', $svg) ?? $svg;
        $svg = preg_replace('/ on\w+="[^"]*"/i', '', $svg) ?? $svg;
        $svg = preg_replace("/ on\\w+='[^']*'/i", '', $svg) ?? $svg;

        return preg_replace('/xlink:href=["\']\s*javascript:[^"\']*["\']/i', 'xlink:href="#"', $svg) ?? $svg;
    }
}
