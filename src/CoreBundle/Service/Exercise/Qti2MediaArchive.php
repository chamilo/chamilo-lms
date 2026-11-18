<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

use const LIBXML_HTML_NODEFDTD;
use const LIBXML_HTML_NOIMPLIED;
use const LIBXML_NONET;

/**
 * Strict, archive-local image transport for QTI 2.1 question HTML.
 *
 * Resolving source URLs and storing imported files are deliberately delegated to
 * callers, where course/session authorization and ResourceNode ownership are known.
 * This class never makes HTTP requests and never extracts ZIP entries to disk.
 */
final class Qti2MediaArchive
{
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    private const MAX_ARCHIVE_BYTES = 20 * 1024 * 1024;
    private const MAX_IMAGES = 40;
    private const MAX_DIMENSION = 8192;
    private const MAX_PIXELS = 20_000_000;
    private const DIRECTORY = 'qti_media/';

    /**
     * @var array<string, true>
     */
    private array $added = [];
    private int $totalBytes = 0;

    /**
     * @param callable(string): array{content: string, filename: string}|null $resolveImage
     */
    public function exportHtml(string $html, ZipArchive $archive, callable $resolveImage): string
    {
        return $this->rewriteImages(
            $html,
            function (string $src) use ($archive, $resolveImage): string {
                $result = $resolveImage($src);
                if (null === $result || !isset($result['content'], $result['filename'])) {
                    throw new InvalidArgumentException('The QTI image does not resolve to an authorized local file.');
                }

                return $this->addImage($archive, $result['content'], $result['filename']);
            }
        );
    }

    /**
     * @param callable(string, string, string): string $storeImage receives data, filename and MIME, returns an authorized URL
     */
    public function importHtml(string $html, ZipArchive $archive, callable $storeImage): string
    {
        $imported = [];

        return $this->rewriteImages(
            $html,
            function (string $src) use ($archive, $storeImage, &$imported): string {
                if (!isset($imported[$src])) {
                    [$contents, $filename, $mime] = self::readImage($archive, $src);
                    $url = $storeImage($contents, $filename, $mime);
                    if ('' === trim($url)) {
                        throw new RuntimeException('The imported image has no valid resource URL.');
                    }
                    $imported[$src] = $url;
                }

                return $imported[$src];
            }
        );
    }

    public function addImage(ZipArchive $archive, string $contents, string $originalFilename): string
    {
        $mime = self::validateImage($contents);
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
        };
        $path = self::DIRECTORY.hash('sha256', $contents).'.'.$extension;
        if (isset($this->added[$path])) {
            return $path;
        }
        if (\count($this->added) >= self::MAX_IMAGES || $this->totalBytes + \strlen($contents) > self::MAX_ARCHIVE_BYTES) {
            throw new InvalidArgumentException('QTI image limits exceeded.');
        }
        if (!$archive->addFromString($path, $contents)) {
            throw new RuntimeException('Could not add the image to the QTI archive.');
        }
        $this->added[$path] = true;
        $this->totalBytes += \strlen($contents);

        return $path;
    }

    /**
     * @return array{string, string, string} image data, safe filename and MIME
     */
    public static function readImage(ZipArchive $archive, string $path): array
    {
        if (!preg_match('#^qti_media/([0-9a-f]{64})\.(png|jpg|gif)$#D', $path, $matches)) {
            throw new InvalidArgumentException('Invalid QTI image archive path.');
        }
        $stat = $archive->statName($path);
        if (!\is_array($stat) || ($stat['size'] ?? -1) <= 0 || $stat['size'] > self::MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('Invalid or oversized QTI image entry.');
        }
        $index = $archive->locateName($path, 0);
        if (false === $index) {
            throw new InvalidArgumentException('The QTI image is missing from the archive.');
        }
        if ($archive->getExternalAttributesIndex($index, $operatingSystem, $attributes)
            && ZipArchive::OPSYS_UNIX === $operatingSystem && 0o120000 === (($attributes >> 16) & 0o170000)) {
            throw new InvalidArgumentException('QTI image archive entries cannot be symbolic links.');
        }
        $contents = $archive->getFromName($path);
        if (!\is_string($contents) || \strlen($contents) !== $stat['size'] || hash('sha256', $contents) !== $matches[1]) {
            throw new InvalidArgumentException('QTI image content does not match its archive identifier.');
        }
        $mime = self::validateImage($contents);
        $expected = match ($matches[2]) {
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
        };
        if ($mime !== $expected) {
            throw new InvalidArgumentException('QTI image type does not match the file extension.');
        }

        return [$contents, basename($path), $mime];
    }

    private static function validateImage(string $contents): string
    {
        $length = \strlen($contents);
        if (0 === $length || $length > self::MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('The QTI image size is invalid.');
        }
        $details = @getimagesizefromstring($contents);
        if (!\is_array($details)) {
            throw new InvalidArgumentException('Invalid QTI image data.');
        }
        [$width, $height] = $details;
        if ($width <= 0 || $height <= 0 || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION
            || $width * $height > self::MAX_PIXELS) {
            throw new InvalidArgumentException('QTI image dimensions exceed supported limits.');
        }
        $mime = (string) ($details['mime'] ?? '');
        if (!\in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
            throw new InvalidArgumentException('Only PNG, JPEG and GIF QTI images are supported.');
        }

        return $mime;
    }

    /**
     * @param callable(string): string $replacement
     */
    private function rewriteImages(string $html, callable $replacement): string
    {
        if ('' === trim($html) || false === stripos($html, '<img')) {
            return $html;
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML('<?xml encoding="UTF-8"?><div id="qti-image-scope">'.$html.'</div>', LIBXML_NONET | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (!$loaded) {
            throw new InvalidArgumentException('Cannot parse QTI answer image HTML.');
        }
        $scope = (new DOMXPath($document))->query('//*[@id="qti-image-scope"]')->item(0);
        if (!$scope instanceof DOMElement) {
            throw new InvalidArgumentException('QTI image HTML has an invalid root.');
        }
        $images = iterator_to_array($scope->getElementsByTagName('img'));
        foreach ($images as $image) {
            $src = trim($image->getAttribute('src'));
            if ('' === $src) {
                throw new InvalidArgumentException('QTI image is missing its src attribute.');
            }
            $image->setAttribute('src', $replacement($src));
            $image->removeAttribute('srcset');
            $image->removeAttribute('data-zoom-image');
        }
        $output = '';
        foreach ($scope->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }
}
