<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use CpChart\Data;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

use const DIRECTORY_SEPARATOR;

final class ThemeHelper
{
    /**
     * Absolute last resort if nothing else is configured.
     * Kept for backward compatibility.
     */
    public const string DEFAULT_THEME = 'chamilo';

    /**
     * Fallback palette (rgba) if no palette file is available in the theme FS.
     * Colors are looped if more are needed.
     */
    private const array FALLBACK_RGBA = [
        'rgba(169,68,66,0.9)',
        'rgba(230,126,34,0.9)',
        'rgba(241,196,15,0.9)',
        'rgba(175,122,197,0.9)',
        'rgba(93,173,226,0.9)',
        'rgba(133,193,233,0.9)',
        'rgba(169,223,191,0.9)',
        'rgba(46,134,193,0.9)',
        'rgba(125,206,160,0.9)',
        'rgba(40,116,166,0.9)',
        'rgba(176,58,46,0.9)',
        'rgba(88,214,141,0.9)',
        'rgba(52,152,219,0.9)',
        'rgba(155,89,182,0.9)',
        'rgba(241,148,138,0.9)',
        'rgba(127,140,141,0.9)',
    ];

    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly SettingsManager $settingsManager,
        private readonly UserHelper $userHelper,
        private readonly CidReqHelper $cidReqHelper,
        private readonly SettingsCourseManager $settingsCourseManager,
        private readonly RouterInterface $router,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        private readonly FilesystemOperator $filesystem,
        // Injected from services.yaml (.env -> THEME_FALLBACK)
        #[Autowire(param: 'theme_fallback')]
        private readonly string $themeFallback = '',
    ) {}

    /**
     * Returns the slug of the theme that should be applied on the current page.
     * Precedence:
     * 1) Active theme bound to current AccessUrl (DB relation)
     * 2) User-selected theme (if enabled)
     * 3) Course/LP theme (if enabled)
     * 4) THEME_FALLBACK from .env
     * 5) DEFAULT_THEME ('chamilo').
     */
    public function getVisualTheme(): string
    {
        static $visualTheme;

        global $lp_theme_css;

        if (isset($visualTheme)) {
            return $visualTheme;
        }

        $visualTheme = null;
        $accessUrl = $this->accessUrlHelper->getCurrent();

        // 1) Active theme bound to current AccessUrl (DB relation)
        if ($accessUrl instanceof AccessUrl) {
            $visualTheme = $accessUrl->getActiveColorTheme()?->getColorTheme()->getSlug();
        }

        // 2) User-selected theme (if setting is enabled)
        if ('true' === $this->settingsManager->getSetting('profile.user_selected_theme')) {
            $visualTheme = $this->userHelper->getCurrent()?->getTheme() ?: $visualTheme;
        }

        // 3) Course theme / Learning path theme (if setting is enabled)
        if ('true' === $this->settingsManager->getSetting('course.allow_course_theme')) {
            $course = $this->cidReqHelper->getCourseEntity();

            if ($course) {
                $this->settingsCourseManager->setCourse($course);

                $courseTheme = (string) $this->settingsCourseManager->getCourseSettingValue('course_theme');
                if ('' !== $courseTheme) {
                    $visualTheme = $courseTheme;
                }

                if (1 === (int) $this->settingsCourseManager->getCourseSettingValue('allow_learning_path_theme')) {
                    if (!empty($lp_theme_css)) {
                        $visualTheme = $lp_theme_css;
                    }
                }
            }
        }

        // 4) .env fallback if still empty
        if (null === $visualTheme || '' === $visualTheme) {
            $fallback = trim((string) $this->themeFallback);
            $visualTheme = '' !== $fallback ? $fallback : self::DEFAULT_THEME;
        }

        return $visualTheme;
    }

    /**
     * Decide the theme in which the requested asset actually exists.
     * This prevents 404 when the file is only present in DEFAULT_THEME.
     */
    private function resolveAssetTheme(string $path): ?string
    {
        $visual = $this->getVisualTheme();

        try {
            if ($this->filesystem->fileExists($visual.DIRECTORY_SEPARATOR.$path)) {
                return $visual;
            }
            if ($this->filesystem->fileExists(self::DEFAULT_THEME.DIRECTORY_SEPARATOR.$path)) {
                return self::DEFAULT_THEME;
            }
        } catch (FilesystemException) {
            return null;
        }

        return null;
    }

    /**
     * Resolves a themed file location checking the selected theme first,
     * then falling back to DEFAULT_THEME as a last resort.
     */
    public function getFileLocation(string $path): ?string
    {
        $assetTheme = $this->resolveAssetTheme($path);
        if (null === $assetTheme) {
            return null;
        }

        return $assetTheme.DIRECTORY_SEPARATOR.$path;
    }

    /**
     * Build a URL for the theme asset, using the theme where the file actually exists.
     */
    public function getThemeAssetUrl(string $path, bool $absoluteUrl = false): string
    {
        $assetTheme = $this->resolveAssetTheme($path);
        if (null === $assetTheme) {
            return '';
        }

        return $this->router->generate(
            'theme_asset',
            ['name' => $assetTheme, 'path' => $path],
            $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }

    /**
     * Convenience helper to emit a <link> tag for a theme asset.
     */
    public function getThemeAssetLinkTag(string $path, bool $absoluteUrl = false): string
    {
        $url = $this->getThemeAssetUrl($path, $absoluteUrl);
        if ('' === $url) {
            return '';
        }

        return \sprintf('<link rel="stylesheet" href="%s">', $url);
    }

    public function getThemeAssetScriptTag(string $path, bool $absoluteUrl = false): string
    {
        $url = $this->getThemeAssetUrl($path, $absoluteUrl);

        if ('' === $url) {
            return '';
        }

        return \sprintf('<script src="%s"></script>', $url);
    }

    /**
     * Read raw contents from the themed filesystem.
     */
    public function getAssetContents(string $path): string
    {
        try {
            $fullPath = $this->getFileLocation($path);
            if ($fullPath) {
                $stream = $this->filesystem->readStream($fullPath);
                $contents = \is_resource($stream) ? stream_get_contents($stream) : false;
                if (\is_resource($stream)) {
                    fclose($stream);
                }

                return false !== $contents ? $contents : '';
            }
        } catch (FilesystemException) {
            return '';
        }

        return '';
    }

    /**
     * Return a Base64-encoded data URI for the given themed asset.
     */
    public function getAssetBase64Encoded(string $path): string
    {
        try {
            $fullPath = $this->getFileLocation($path);
            if ($fullPath) {
                $detector = new ExtensionMimeTypeDetector();
                $mimeType = (string) $detector->detectMimeTypeFromFile($fullPath);
                $data = $this->getAssetContents($path);

                return '' !== $data
                    ? 'data:'.$mimeType.';base64,'.base64_encode($data)
                    : '';
            }
        } catch (FilesystemException) {
            return '';
        }

        return '';
    }

    /**
     * Return the preferred logo URL for current theme (header/email),
     * falling back to DEFAULT_THEME if needed.
     *
     * For emails: try PNG candidates first, then SVG candidates, all within the
     * active theme before falling back to the default Chamilo theme. This prevents
     * themes that only provide SVG logos from silently showing the default Chamilo
     * logo in outgoing emails.
     */
    public function getPreferredLogoUrl(string $type = 'header', bool $absoluteUrl = false): string
    {
        if ('email' === $type) {
            // Priority order for email logos (PNG preferred, SVG accepted as fallback).
            $emailCandidates = [
                'images/email-logo.png',
                'images/header-logo.png',
                'images/email-logo.svg',
                'images/header-logo.svg',
            ];

            $visual = $this->getVisualTheme();

            // 1) Look for any logo variant in the active theme first.
            if (self::DEFAULT_THEME !== $visual) {
                foreach ($emailCandidates as $relPath) {
                    try {
                        if ($this->filesystem->fileExists($visual.DIRECTORY_SEPARATOR.$relPath)) {
                            return $this->router->generate(
                                'theme_asset',
                                ['name' => $visual, 'path' => $relPath],
                                $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH
                            );
                        }
                    } catch (FilesystemException) {
                        // continue to next candidate
                    }
                }
            }

            // 2) Nothing in the active theme — fall back to the default theme (PNG only).
            foreach (['images/email-logo.png', 'images/header-logo.png'] as $relPath) {
                $url = $this->getThemeAssetUrl($relPath, $absoluteUrl);
                if ('' !== $url) {
                    return $url;
                }
            }

            return '';
        }

        // Non-email (header, etc.): original behaviour.
        $candidates = ['images/header-logo.svg', 'images/header-logo.png'];

        foreach ($candidates as $relPath) {
            $url = $this->getThemeAssetUrl($relPath, $absoluteUrl);
            if ('' !== $url) {
                return $url;
            }
        }

        return '';
    }

    /**
     * Default palette file path (inside the theme FS):
     * - palettes/pchart/default.color
     *
     * @return string[]
     */
    public function getColorPalette(
        bool $decimalOpacity = false,
        bool $wrapInRGBA = false,
        int $fillUpTo = 0,
        string $palettePath = 'palettes/pchart/default.color',
    ): array {
        $raw = $this->getAssetContents($palettePath);

        if ('' === trim($raw)) {
            // Theme does not provide the palette file; use fallback.
            return $this->fillUpPalette(self::FALLBACK_RGBA, $fillUpTo);
        }

        try {
            $colors = PaletteFile::parse($raw);
        } catch (InvalidArgumentException) {
            return $this->fillUpPalette(self::FALLBACK_RGBA, $fillUpTo);
        }

        $palette = [];
        foreach ($colors as [$r, $g, $b, $alpha]) {
            if ($wrapInRGBA) {
                // CSS rgba expects alpha between 0 and 1, unlike pChart's 0..100.
                $opacity = $decimalOpacity ? round($alpha / 100, 1) : round($alpha / 100, 2);
                $palette[] = \sprintf('rgba(%d,%d,%d,%s)', $r, $g, $b, (string) $opacity);
            } else {
                $opacity = $decimalOpacity ? (string) round($alpha / 100, 1) : (string) $alpha;
                $palette[] = \sprintf('%d,%d,%d,%s', $r, $g, $b, $opacity);
            }
        }

        return $this->fillUpPalette($palette, $fillUpTo);
    }

    /**
     * Use a themed palette with pChart even when themes live in remote storage.
     * Returns false if there is no valid override, allowing existing charts
     * to retain their bundled pChart palette as before.
     */
    public function applyPchartPalette(Data $data): bool
    {
        $raw = $this->getAssetContents('palettes/pchart/default.color');
        if ('' === trim($raw)) {
            return false;
        }

        try {
            $colors = PaletteFile::parse($raw);
        } catch (InvalidArgumentException) {
            return false;
        }

        $series = array_keys((array) ($data->getData()['Series'] ?? []));
        foreach ($series as $index => $name) {
            [$r, $g, $b, $alpha] = $colors[$index % \count($colors)];
            $data->setPalette((string) $name, [
                'R' => $r,
                'G' => $g,
                'B' => $b,
                'Alpha' => $alpha,
            ]);
        }

        return true;
    }

    /**
     * @param string[] $palette
     *
     * @return string[]
     */
    private function fillUpPalette(array $palette, int $fillUpTo): array
    {
        if ($fillUpTo <= 0) {
            return $palette;
        }

        $count = \count($palette);
        if ($count <= 0) {
            return $palette;
        }

        if ($fillUpTo <= $count) {
            return \array_slice($palette, 0, $fillUpTo);
        }

        for ($i = $count; $i < $fillUpTo; $i++) {
            $palette[$i] = $palette[$i % $count];
        }

        return $palette;
    }
}
