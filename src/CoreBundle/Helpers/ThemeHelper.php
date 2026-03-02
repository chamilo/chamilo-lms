<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
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
    public const DEFAULT_THEME = 'chamilo';

    /**
     * Fallback palette (rgba) if no palette file is available in the theme FS.
     * Colors are looped if more are needed.
     */
    private const FALLBACK_RGBA = [
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
     */
    public function getPreferredLogoUrl(string $type = 'header', bool $absoluteUrl = false): string
    {
        // For e-mails, prefer PNG only (Gmail often blocks SVG rendering in emails).
        $candidates = 'email' === $type
            ? ['images/email-logo.png', 'images/header-logo.png']
            : ['images/header-logo.svg', 'images/header-logo.png'];

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

        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $palette = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ('' === $line) {
                continue;
            }

            $components = preg_split('/\s*,\s*/', $line);
            if (!\is_array($components) || \count($components) < 3) {
                continue;
            }

            $r = (int) ($components[0] ?? 0);
            $g = (int) ($components[1] ?? 0);
            $b = (int) ($components[2] ?? 0);

            // Alpha is optional in some palettes; default to 1.0
            $aRaw = $components[3] ?? '1';
            $a = is_numeric($aRaw) ? (float) $aRaw : 1.0;

            // optional conversion: 0..100 -> 0..1 (1 decimal)
            if ($decimalOpacity && $a > 1.0) {
                $a = round($a / 100.0, 1);
            }

            // When wrapping as rgba(), enforce CSS alpha normalization
            if ($wrapInRGBA) {
                if ($a > 1.0) {
                    // C1 alpha is frequently 0..100
                    $a = round($a / 100.0, 2);
                }
                $a = max(0.0, min(1.0, $a));

                $palette[] = \sprintf(
                    'rgba(%d,%d,%d,%s)',
                    $r,
                    $g,
                    $b,
                    rtrim(rtrim((string) $a, '0'), '.')
                );
            } else {
                $palette[] = \sprintf('%d,%d,%d,%s', $r, $g, $b, (string) $a);
            }
        }

        if (empty($palette)) {
            return $this->fillUpPalette(self::FALLBACK_RGBA, $fillUpTo);
        }

        return $this->fillUpPalette($palette, $fillUpTo);
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
