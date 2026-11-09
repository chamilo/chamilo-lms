<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Helpers\PaletteFile;
use Chamilo\CoreBundle\Helpers\ThemeHelper;
use DateTimeImmutable;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const IMAGETYPE_JPEG;
use const IMAGETYPE_PNG;
use const IMAGETYPE_WEBP;
use const PATHINFO_EXTENSION;

#[Route('/themes')]
class ThemeController extends AbstractController
{
    private const string PAGE_BACKGROUND_SLOT_REQUIREMENT = 'landscape|cityscape|fieldscape|seascape';
    private const int PAGE_BACKGROUND_MAX_BYTES = 2 * 1024 * 1024;

    public function __construct(
        private readonly ThemeHelper $themeHelper
    ) {}

    /**
     * Admin-managed pChart palette for the active visual theme.
     * Files stay in the themes Flysystem, including remote-storage deployments.
     */
    #[Route('/{slug}/palette', name: 'theme_palette_status', methods: ['GET'], priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function paletteStatus(
        string $slug,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        FilesystemOperator $fs
    ): JsonResponse {
        $this->assertActiveTheme($slug);
        $path = $slug.'/palettes/pchart/default.color';
        $exists = $fs->fileExists($path);

        return $this->json([
            'uploaded' => $exists,
            'themeDirectoryExists' => $fs->directoryExists($slug),
        ]);
    }

    #[Route('/{slug}/palette', name: 'theme_palette_upload', methods: ['POST'], priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function uploadPalette(
        string $slug,
        Request $request,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        FilesystemOperator $fs
    ): JsonResponse {
        $this->assertActiveTheme($slug);
        $file = $request->files->get('palette');
        if (!$file instanceof UploadedFile || !$file->isValid() || 'color' !== strtolower($file->getClientOriginalExtension())) {
            return $this->json(['error' => 'Select a valid .color palette file.'], Response::HTTP_BAD_REQUEST);
        }

        if (($file->getSize() ?? 0) > 16384) {
            return $this->json(['error' => 'The palette file cannot exceed 16 KB.'], Response::HTTP_BAD_REQUEST);
        }

        $contents = file_get_contents($file->getPathname());
        if (false === $contents) {
            return $this->json(['error' => 'Cannot read the palette file.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $colors = PaletteFile::parse($contents);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if (!$fs->directoryExists($slug)) {
            return $this->json(
                ['error' => 'The active visual theme has no assets directory. Install its theme files or select another theme to upload a chart palette.'],
                Response::HTTP_NOT_FOUND
            );
        }
        $this->ensureDir($fs, $slug.'/palettes');
        $this->ensureDir($fs, $slug.'/palettes/pchart');
        $fs->write($slug.'/palettes/pchart/default.color', $contents);

        return $this->json(['uploaded' => true, 'colors' => \count($colors)], Response::HTTP_CREATED);
    }

    #[Route('/{slug}/palette', name: 'theme_palette_delete', methods: ['DELETE'], priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function deletePalette(
        string $slug,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        FilesystemOperator $fs
    ): JsonResponse {
        $this->assertActiveTheme($slug);
        $path = $slug.'/palettes/pchart/default.color';
        if ($fs->fileExists($path)) {
            $fs->delete($path);
        }

        return $this->json(['uploaded' => false]);
    }

    /**
     * The images at the bottom of the pages of the active visual theme: what each slot shows, and
     * whether the administrator replaced or removed it.
     */
    #[Route('/{slug}/page-backgrounds', name: 'theme_page_backgrounds', methods: ['GET'], priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function pageBackgrounds(string $slug): JsonResponse
    {
        $this->assertActiveTheme($slug);

        return $this->json($this->describePageBackgrounds($slug));
    }

    #[Route(
        '/{slug}/page-backgrounds/{slot}',
        name: 'theme_page_background_upload',
        requirements: ['slot' => self::PAGE_BACKGROUND_SLOT_REQUIREMENT],
        methods: ['POST'],
        priority: 10
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function uploadPageBackground(
        string $slug,
        string $slot,
        Request $request,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        FilesystemOperator $fs
    ): JsonResponse {
        $this->assertActiveTheme($slug);
        $file = $request->files->get('image');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'Select an image file.'], Response::HTTP_BAD_REQUEST);
        }

        if (($file->getSize() ?: 0) > self::PAGE_BACKGROUND_MAX_BYTES) {
            return $this->json(['error' => 'The image cannot exceed 2 MB.'], Response::HTTP_BAD_REQUEST);
        }

        // The type is read from the image itself, not from the name or the type the browser sent
        $info = @getimagesize($file->getPathname());
        $extension = match (false === $info ? null : $info[2]) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_WEBP => 'webp',
            default => null,
        };
        if (null === $extension) {
            return $this->json(['error' => 'Select a PNG, JPEG or WebP image.'], Response::HTTP_BAD_REQUEST);
        }

        $choices = $this->themeHelper->getPageBackgroundChoices($slug);
        $previousPath = $choices[$slot]['file'] ?? null;
        // A name of its own, so that the image the theme ships stays as the default
        $path = 'images/bg-'.$slot.'-custom.'.$extension;

        $this->ensureDir($fs, $slug);
        $this->ensureDir($fs, $slug.'/images');
        $stream = fopen($file->getPathname(), 'rb');
        $fs->writeStream($slug.'/'.$path, $stream);
        if (\is_resource($stream)) {
            fclose($stream);
        }
        if (null !== $previousPath && $previousPath !== $path && $fs->fileExists($slug.'/'.$previousPath)) {
            $fs->delete($slug.'/'.$previousPath);
        }

        $choices[$slot] = ['file' => $path, 'version' => time()];
        $this->themeHelper->savePageBackgroundChoices($slug, $choices);

        return $this->json($this->describePageBackgrounds($slug), Response::HTTP_CREATED);
    }

    /**
     * Removes the image of a slot from the pages, or shows it again, without losing an uploaded one.
     */
    #[Route(
        '/{slug}/page-backgrounds/{slot}',
        name: 'theme_page_background_visibility',
        requirements: ['slot' => self::PAGE_BACKGROUND_SLOT_REQUIREMENT],
        methods: ['PATCH'],
        priority: 10
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function updatePageBackgroundVisibility(string $slug, string $slot, Request $request): JsonResponse
    {
        $this->assertActiveTheme($slug);
        $hidden = json_decode($request->getContent(), true)['hidden'] ?? null;
        if (!\is_bool($hidden)) {
            return $this->json(['error' => 'Tell whether the image is hidden.'], Response::HTTP_BAD_REQUEST);
        }

        $choices = $this->themeHelper->getPageBackgroundChoices($slug);
        unset($choices[$slot]['hidden']);
        if ($hidden) {
            $choices[$slot]['hidden'] = true;
        }
        $this->themeHelper->savePageBackgroundChoices($slug, $choices);

        return $this->json($this->describePageBackgrounds($slug));
    }

    /**
     * Restores the image the theme ships for a slot: deletes the uploaded one and shows it again.
     */
    #[Route(
        '/{slug}/page-backgrounds/{slot}',
        name: 'theme_page_background_delete',
        requirements: ['slot' => self::PAGE_BACKGROUND_SLOT_REQUIREMENT],
        methods: ['DELETE'],
        priority: 10
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function deletePageBackground(
        string $slug,
        string $slot,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        FilesystemOperator $fs
    ): JsonResponse {
        $this->assertActiveTheme($slug);
        $choices = $this->themeHelper->getPageBackgroundChoices($slug);
        $path = $choices[$slot]['file'] ?? null;
        if (null !== $path && $fs->fileExists($slug.'/'.$path)) {
            $fs->delete($slug.'/'.$path);
        }

        unset($choices[$slot]);
        $this->themeHelper->savePageBackgroundChoices($slug, $choices);

        return $this->json($this->describePageBackgrounds($slug));
    }

    private function assertActiveTheme(string $slug): void
    {
        // Prevent path traversal and cross-theme writes, including on shared storage.
        if (!preg_match('/^[a-zA-Z0-9_-]{1,255}$/D', $slug) || $slug !== $this->themeHelper->getVisualTheme()) {
            throw $this->createAccessDeniedException('Only the active visual theme can be modified.');
        }
    }

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
        Request $request,
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

        return $this->streamFile($filesystem, $filePath, $request);
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

        return $this->streamFile($filesystem, $filePath, $request);
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
     * Theme assets are re-uploadable (e.g. logos), so freshness is validated via
     * ETag/Last-Modified (derived from the file's own mtime/size) rather than a
     * blanket no-store: a re-upload changes the mtime and busts the cache automatically.
     */
    private function streamFile(FilesystemOperator $filesystem, string $filePath, Request $request): Response
    {
        $lastModifiedTimestamp = $filesystem->lastModified($filePath);
        $etag = md5($filePath.$lastModifiedTimestamp.$filesystem->fileSize($filePath));

        $conditionalResponse = new Response();
        $conditionalResponse->setLastModified((new DateTimeImmutable())->setTimestamp($lastModifiedTimestamp));
        $conditionalResponse->setEtag($etag);
        $conditionalResponse->setPublic();

        if ($conditionalResponse->isNotModified($request)) {
            return $conditionalResponse;
        }

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

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $extMimeMap = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];
        $mimeType = $extMimeMap[$ext] ?? ($filesystem->mimeType($filePath) ?: 'application/octet-stream');

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            basename($filePath)
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $mimeType);
        $response->setLastModified((new DateTimeImmutable())->setTimestamp($lastModifiedTimestamp));
        $response->setEtag($etag);
        $response->setPublic();
        $response->setMaxAge(86400);

        return $response;
    }

    /**
     * @return list<array{slot: string, imageUrl: string|null, custom: bool, hidden: bool}>
     */
    private function describePageBackgrounds(string $slug): array
    {
        $choices = $this->themeHelper->getPageBackgroundChoices($slug);
        $urls = $this->themeHelper->getPageBackgroundUrls($slug, true);

        return array_map(
            static fn (string $slot): array => [
                'slot' => $slot,
                'imageUrl' => $urls[$slot],
                'custom' => isset($choices[$slot]['file']),
                'hidden' => $choices[$slot]['hidden'] ?? false,
            ],
            ThemeHelper::PAGE_BACKGROUND_SLOTS,
        );
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
