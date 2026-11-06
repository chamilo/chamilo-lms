<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\ColorTheme;

use Chamilo\CoreBundle\Helpers\PaletteFile;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use ZipArchive;

use const IMAGETYPE_PNG;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Portable color-theme packages. Never extracts archive paths to the server filesystem.
 */
final class ColorThemeArchive
{
    private const int MAX_ARCHIVE_BYTES = 10 * 1024 * 1024;
    private const int MAX_ENTRY_BYTES = 2 * 1024 * 1024;
    private const int MAX_TOTAL_BYTES = 12 * 1024 * 1024;
    private const int MAX_ENTRIES = 16;
    private const array OPTIONAL_ASSETS = [
        'images/header-logo.png',
        'images/email-logo.png',
        'palettes/pchart/default.color',
    ];

    /**
     * @param array<string, mixed> $variables
     *
     * @return array<string, string>
     */
    public static function validateVariables(array $variables): array
    {
        if ([] === $variables || \count($variables) > 64 || !\array_key_exists('--color-primary-base', $variables)) {
            throw new InvalidArgumentException('The theme must contain a valid color palette.');
        }

        $result = [];
        foreach ($variables as $key => $value) {
            if (!\is_string($key) || !preg_match('/^--color-[a-z0-9-]{1,60}$/D', $key) || !\is_string($value)) {
                throw new InvalidArgumentException('Invalid theme color variable.');
            }

            if (!preg_match('/^(-?\d{1,3})\s+(-?\d{1,3})\s+(-?\d{1,3})$/D', trim($value), $matches)) {
                throw new InvalidArgumentException('Invalid theme color value.');
            }
            foreach (\array_slice($matches, 1) as $channel) {
                if ((int) $channel < -255 || (int) $channel > 255) {
                    throw new InvalidArgumentException('Theme color channel is out of range.');
                }
            }
            $result[$key] = implode(' ', \array_slice($matches, 1));
        }

        return $result;
    }

    /**
     * @param array<string, string> $variables
     */
    public static function colorCss(array $variables): string
    {
        $lines = [':root {'];
        foreach (self::validateVariables($variables) as $key => $value) {
            $lines[] = "  $key: $value;";
        }
        $lines[] = '}';

        return implode("\n", $lines)."\n";
    }

    /**
     * @param array<string, string> $variables
     * @param array<string, string> $assets
     */
    public static function write(string $path, string $name, array $variables, array $assets = []): void
    {
        $variables = self::validateVariables($variables);
        $name = self::validateTitle($name);
        self::validateAssets($assets);

        $zip = new ZipArchive();
        if (true !== $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new RuntimeException('Cannot create theme archive.');
        }

        try {
            $metadata = json_encode([
                'formatVersion' => 1,
                'chamiloVersion' => '3.1',
                'name' => $name,
                'variables' => $variables,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $zip->addFromString('theme.json', $metadata."\n");
            foreach ($assets as $asset => $content) {
                $zip->addFromString($asset, $content);
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array{name: string, variables: array<string, string>, assets: array<string, string>}
     */
    public static function read(string $path): array
    {
        if (!is_file($path) || filesize($path) > self::MAX_ARCHIVE_BYTES) {
            throw new InvalidArgumentException('Theme archive is missing or exceeds 10 MB.');
        }
        $zip = new ZipArchive();
        if (true !== $zip->open($path)) {
            throw new InvalidArgumentException('Invalid theme ZIP archive.');
        }

        try {
            if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ENTRIES) {
                throw new InvalidArgumentException('Invalid number of theme files.');
            }
            $total = 0;
            $seen = [];
            $assets = [];
            $metadata = null;
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $entry = $zip->statIndex($i);
                $name = $entry['name'] ?? null;
                if (!\is_string($name) || isset($seen[$name]) || !self::allowedEntry($name)) {
                    throw new InvalidArgumentException('Unsupported or unsafe theme archive entry.');
                }
                $seen[$name] = true;
                $size = (int) ($entry['size'] ?? 0);
                if ($size > self::MAX_ENTRY_BYTES || ($total += $size) > self::MAX_TOTAL_BYTES) {
                    throw new InvalidArgumentException('Theme archive is too large.');
                }
                // Reject symlinks and other non-regular entries.
                if ($zip->getExternalAttributesIndex($i, $opsys, $attributes)
                    && 3 === $opsys
                    && 0 !== ($attributes >> 16 & 0170000)
                    && 0100000 !== ($attributes >> 16 & 0170000)) {
                    throw new InvalidArgumentException('Unsupported theme archive file type.');
                }
                $content = $zip->getFromIndex($i);
                if (!\is_string($content) || \strlen($content) !== $size) {
                    throw new InvalidArgumentException('Cannot read theme archive entry.');
                }
                if ('theme.json' === $name) {
                    $metadata = $content;
                } else {
                    $assets[$name] = $content;
                }
            }
            if (null === $metadata) {
                throw new InvalidArgumentException('Missing theme.json description.');
            }

            try {
                $info = json_decode($metadata, true, 32, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('Invalid theme.json description.', 0, $exception);
            }
            if (!\is_array($info) || 1 !== ($info['formatVersion'] ?? null) || '3.1' !== ($info['chamiloVersion'] ?? null)
                || !\is_string($info['name'] ?? null) || !\is_array($info['variables'] ?? null)) {
                throw new InvalidArgumentException('Unsupported theme description or Chamilo version.');
            }
            self::validateAssets($assets);

            return [
                'name' => self::validateTitle($info['name']),
                'variables' => self::validateVariables($info['variables']),
                'assets' => $assets,
            ];
        } finally {
            $zip->close();
        }
    }

    public static function validateTitle(string $title): string
    {
        $title = trim($title);
        if ('' === $title || mb_strlen($title) > 100 || preg_match('/[\x00-\x1f\x7f]/', $title)) {
            throw new InvalidArgumentException('Theme name must be between 1 and 100 characters.');
        }

        return $title;
    }

    /**
     * @return list<string>
     */
    public static function assetPaths(): array
    {
        return self::OPTIONAL_ASSETS;
    }

    private static function allowedEntry(string $name): bool
    {
        return 'theme.json' === $name || \in_array($name, self::OPTIONAL_ASSETS, true);
    }

    /**
     * @param array<string, string> $assets
     */
    private static function validateAssets(array $assets): void
    {
        foreach ($assets as $path => $content) {
            if (!self::allowedEntry($path) || 'theme.json' === $path || !\is_string($content)) {
                throw new InvalidArgumentException('Unsupported theme asset.');
            }
            if (\strlen($content) > self::MAX_ENTRY_BYTES) {
                throw new InvalidArgumentException('Theme asset is too large.');
            }
            if (str_ends_with($path, '.png')) {
                $image = @getimagesizefromstring($content);
                if (false === $image || IMAGETYPE_PNG !== $image[2] || $image[0] > 2048 || $image[1] > 2048) {
                    throw new InvalidArgumentException('Invalid theme PNG image.');
                }
            } elseif ('palettes/pchart/default.color' === $path) {
                PaletteFile::parse($content);
            }
        }
    }
}
