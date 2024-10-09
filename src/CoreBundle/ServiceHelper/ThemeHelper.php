<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

use const DIRECTORY_SEPARATOR;
use const PHP_SAPI;

final class ThemeHelper
{
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
    ) {}

    /**
     * Returns the name of the color theme configured to be applied on the current page.
     * The returned name depends on the platform, course or user settings.
     */
    public function getVisualTheme(): string
    {
        if ('cli' === PHP_SAPI) {
            return '';
        }

        static $visualTheme;

        global $lp_theme_css;

        if (isset($visualTheme)) {
            return $visualTheme;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();

        $visualTheme = $accessUrl->getActiveColorTheme()?->getColorTheme()->getSlug();

        if ('true' == $this->settingsManager->getSetting('profile.user_selected_theme')) {
            $visualTheme = $this->userHelper->getCurrent()?->getTheme();
        }

        if ('true' == $this->settingsManager->getSetting('course.allow_course_theme')) {
            $course = $this->cidReqHelper->getCourseEntity();

            if ($course) {
                $this->settingsCourseManager->setCourse($course);

                $visualTheme = $this->settingsCourseManager->getCourseSettingValue('course_theme');

                if (1 === (int) $this->settingsCourseManager->getCourseSettingValue('allow_learning_path_theme')) {
                    $visualTheme = $lp_theme_css;
                }
            }
        }

        if (empty($visualTheme)) {
            $visualTheme = self::DEFAULT_THEME;
        }

        return $visualTheme;
    }

    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     */
    public function getFileLocation(string $path): ?string
    {
        $themeName = $this->getVisualTheme();

        $locations = [
            $themeName.DIRECTORY_SEPARATOR.$path,
            self::DEFAULT_THEME.DIRECTORY_SEPARATOR.$path,
        ];

        foreach ($locations as $location) {
            if ($this->filesystem->fileExists($location)) {
                return $location;
            }
        }

        return null;
    }

    public function getThemeAssetUrl(string $path, bool $absoluteUrl = false): string
    {
        try {
            if (!$this->getFileLocation($path)) {
                return '';
            }
        } catch (FilesystemException) {
            return '';
        }

        $themeName = $this->getVisualTheme();

        return $this->router->generate(
            'theme_asset',
            ['name' => $themeName, 'path' => $path],
            $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }

    public function getThemeAssetLinkTag(string $path, bool $absoluteUrl = false): string
    {
        $url = $this->getThemeAssetUrl($path, $absoluteUrl);

        if (empty($url)) {
            return '';
        }

        return \sprintf('<link rel="stylesheet" href="%s">', $url);
    }

    public function getAssetContents(string $path): string
    {
        try {
            if ($fullPath = $this->getFileLocation($path)) {
                $stream = $this->filesystem->readStream($fullPath);

                $contents = stream_get_contents($stream);

                fclose($stream);

                return $contents;
            }
        } catch (FilesystemException|UnableToReadFile) {
            return '';
        }

        return '';
    }

    public function getAssetBase64Encoded(string $path): string
    {
        try {
            if ($fullPath = $this->getFileLocation($path)) {
                $detector = new ExtensionMimeTypeDetector();
                $mimeType = (string) $detector->detectMimeTypeFromFile($fullPath);

                return 'data:'.$mimeType.';base64,'.base64_encode($this->getAssetContents($path));
            }
        } catch (FilesystemException) {
            return '';
        }

        return '';
    }
}
