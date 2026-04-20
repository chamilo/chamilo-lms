<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Parser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;
use Exception;

/**
 * Class PackageParser.
 */
abstract class PackageParser
{
    protected string $filePath;
    protected Course $course;
    protected ?Session $session;

    protected function __construct(string $filePath, Course $course, ?Session $session = null)
    {
        $this->filePath = $filePath;
        $this->course = $course;
        $this->session = $session;
    }

    /**
     * @throws Exception
     */
    public static function create(string $packageType, string $filePath, Course $course, ?Session $session = null)
    {
        switch ($packageType) {
            case 'tincan':
                return new TinCanParser($filePath, $course, $session);

            case 'cmi5':
                return new Cmi5Parser($filePath, $course, $session);

            default:
                throw new Exception('Invalid package.');
        }
    }

    abstract public function parse(): XApiToolLaunch;

    protected function readPackageFileContents(?string $path = null): string
    {
        $path ??= $this->filePath;

        if ($this->isStorageUri($path)) {
            $relativePath = $this->getStorageRelativePath($path);
            $content = Container::getPluginsFileSystem()->read($relativePath);

            return is_string($content) ? $content : (string) $content;
        }

        $content = file_get_contents($path);
        if (false === $content) {
            throw new Exception(sprintf('Unable to read package file "%s".', $path));
        }

        return $content;
    }

    /**
     * Resolve a package-relative URL to a browser-accessible URL in Chamilo 2.
     *
     * Supported cases:
     * - absolute URLs: returned as-is
     * - files extracted under public/: mapped directly to WEB_PUBLIC_PATH
     * - files stored under the plugin filesystem: served through package_asset.php
     * - legacy files extracted under var/plugins/XApi/: served through package_asset.php
     *
     * @throws Exception
     */
    protected function resolvePackageUrl(string $url): string
    {
        $url = trim($url);

        if ('' === $url) {
            return '';
        }

        if ($this->isAbsoluteUrl($url)) {
            return $url;
        }

        $relativePath = ltrim($url, '/');

        if ($this->isStorageUri($this->filePath)) {
            $packageDirectory = $this->normalizeRelativeStoragePath(dirname($this->getStorageRelativePath($this->filePath)));
            $packageRelativePath = $this->normalizeRelativeStoragePath(trim($packageDirectory.'/'.$relativePath, '/'));

            if (null === $packageRelativePath) {
                throw new Exception('Invalid package URL.');
            }

            return $this->buildPackageAssetUrl($packageRelativePath);
        }

        $packageDirectory = $this->normalizePath(dirname($this->filePath));

        $publicBasePath = $this->normalizePath(api_get_path(SYS_PUBLIC_PATH));
        if ($this->pathStartsWith($packageDirectory, $publicBasePath)) {
            $relativeDirectory = ltrim(substr($packageDirectory, strlen($publicBasePath)), '/');

            return rtrim(api_get_path(WEB_PUBLIC_PATH), '/').'/'.trim($relativeDirectory.'/'.$relativePath, '/');
        }

        $pluginStorageBasePath = $this->getPluginStorageBasePath();
        if ($this->pathStartsWith($packageDirectory, $pluginStorageBasePath)) {
            $relativeDirectory = ltrim(substr($packageDirectory, strlen($pluginStorageBasePath)), '/');
            $packageRelativePath = $this->normalizeRelativeStoragePath(trim($relativeDirectory.'/'.$relativePath, '/'));

            if (null === $packageRelativePath) {
                throw new Exception('Invalid package URL.');
            }

            return $this->buildPackageAssetUrl($packageRelativePath);
        }

        throw new Exception('Package directory is not web accessible. Unable to resolve launch URL.');
    }

    protected function buildPackageAssetUrl(string $packageRelativePath): string
    {
        $query = [
            'path' => $packageRelativePath,
            'cid' => $this->course->getId(),
            'gid' => 0,
        ];

        if (null !== $this->session) {
            $query['sid'] = $this->session->getId();
        }

        return api_get_path(WEB_PLUGIN_PATH).'XApi/package_asset.php?'.http_build_query(
            $query,
            '',
            '&',
            PHP_QUERY_RFC3986
        );
    }

    protected function getPluginStorageBasePath(): string
    {
        return $this->normalizePath(Container::getProjectDir().'/var/plugins/XApi');
    }

    protected function isStorageUri(string $path): bool
    {
        return str_starts_with($path, 'storage://');
    }

    protected function getStorageRelativePath(string $path): string
    {
        return ltrim(substr($path, strlen('storage://')), '/');
    }

    protected function normalizeRelativeStoragePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ('' === $path) {
            return null;
        }

        $segments = explode('/', $path);
        $normalized = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if ('' === $segment || '.' === $segment) {
                continue;
            }

            if ('..' === $segment) {
                if (empty($normalized)) {
                    return null;
                }

                array_pop($normalized);
                continue;
            }

            $normalized[] = $segment;
        }

        if (empty($normalized)) {
            return null;
        }

        return implode('/', $normalized);
    }

    protected function normalizePath(string $path): string
    {
        $realPath = realpath($path);
        if (false !== $realPath) {
            $path = $realPath;
        }

        $path = str_replace('\\', '/', $path);

        return rtrim($path, '/');
    }

    protected function pathStartsWith(string $path, string $prefix): bool
    {
        return 0 === strpos($path, $prefix);
    }

    protected function isAbsoluteUrl(string $url): bool
    {
        if (str_starts_with($url, '//')) {
            return true;
        }

        $urlInfo = parse_url($url);

        return !empty($urlInfo['scheme']);
    }
}
