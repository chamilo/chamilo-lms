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
     * Resolve and serve the preferred logo for a theme without causing frontend 404 probes.
     * Priority order:
     * 1) {theme}/images/<type>-logo.svg
     * 2) {theme}/images/<type>-logo.png
     * 3) default/images/<type>-logo.svg
     * 4) default/images/<type>-logo.png.
     *
     * Example:
     * - /themes/beeznest/logo/header
     * - /themes/beeznest/logo/email
     */
    #[Route(
        '/{slug}/logo/{type}',
        name: 'theme_logo',
        requirements: ['type' => 'header|email'],
        methods: ['GET'],
        priority: 20
    )]
    public function logo(
        string $slug,
        string $type,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        FilesystemOperator $filesystem
    ): Response {
        $themeDir = basename($slug);
        $defaultTheme = ThemeHelper::DEFAULT_THEME;

        $relCandidates = 'email' === $type
            ? ['images/email-logo.svg', 'images/email-logo.png']
            : ['images/header-logo.svg', 'images/header-logo.png'];

        $filePath = null;

        // Try requested theme first (do not fail if theme folder does not exist)
        foreach ($relCandidates as $rel) {
            $candidate = $themeDir.'/'.$rel;
            if ($filesystem->fileExists($candidate)) {
                $filePath = $candidate;

                break;
            }
        }

        // Fallback to default theme
        if (!$filePath) {
            foreach ($relCandidates as $rel) {
                $candidate = $defaultTheme.'/'.$rel;
                if ($filesystem->fileExists($candidate)) {
                    $filePath = $candidate;

                    break;
                }
            }
        }

        if (!$filePath) {
            throw $this->createNotFoundException('No logo file found.');
        }

        return $this->streamFile($filesystem, $filePath);
    }

    /**
     * Serve an asset from the theme.
     * - If ?strict=1: only serves {name}/{path}. If it doesn't exist -> 404 (no fallback).
     * - If not strict: serves {name}/{path}, then known logo alternates (svg/png), then default theme.
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

        // Normalize path and prevent traversal
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');
        if (str_contains($path, '..')) {
            throw $this->createNotFoundException('The requested file does not exist.');
        }

        $strict = $request->query->getBoolean('strict', false);

        $candidates = [];

        if ($strict) {
            // Strict: only exact file from requested theme
            $candidates[] = $themeDir.'/'.$path;
        } else {
            // Non-strict: theme first, then default theme (with logo svg/png alternates)
            foreach ([$themeDir, ThemeHelper::DEFAULT_THEME] as $slug) {
                $candidates[] = $slug.'/'.$path;

                foreach ($this->getLogoAlternates($path) as $alt) {
                    $candidates[] = $slug.'/'.$alt;
                }
            }
        }

        $filePath = null;
        foreach ($candidates as $candidate) {
            if ($filesystem->fileExists($candidate)) {
                $filePath = $candidate;

                break;
            }
        }

        if (!$filePath) {
            throw $this->createNotFoundException('The requested file does not exist.');
        }

        return $this->streamFile($filesystem, $filePath);
    }

    /**
     * Only for known logo files. Other assets keep the normal behavior.
     */
    private function getLogoAlternates(string $path): array
    {
        return match ($path) {
            'images/header-logo.svg' => ['images/header-logo.png'],
            'images/email-logo.svg' => ['images/email-logo.png'],
            'images/header-logo.png' => ['images/header-logo.svg'],
            'images/email-logo.png' => ['images/email-logo.svg'],
            default => [],
        };
    }

    /**
     * Stream a file from Flysystem with correct mime type.
     */
    private function streamFile(FilesystemOperator $filesystem, string $filePath): Response
    {
        $response = new StreamedResponse(function () use ($filesystem, $filePath): void {
            $out = fopen('php://output', 'wb');
            $in = $filesystem->readStream($filePath);

            if (!\is_resource($out) || !\is_resource($in)) {
                return;
            }

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
            basename($filePath)
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
