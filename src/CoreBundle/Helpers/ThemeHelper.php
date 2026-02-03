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
        $candidates = 'email' === $type
            ? ['images/email-logo.svg', 'images/email-logo.png']
            : ['images/header-logo.svg', 'images/header-logo.png'];

        foreach ($candidates as $relPath) {
            $url = $this->getThemeAssetUrl($relPath, $absoluteUrl);
            if ('' !== $url) {
                return $url;
            }
        }

        return '';
    }
}
